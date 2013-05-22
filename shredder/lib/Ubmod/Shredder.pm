package Ubmod::Shredder;
use strict;
use warnings;
use Carp;
use DateTime;
use File::Spec;

sub factory {
    my ( $class, %options ) = @_;

    confess "Format required"          unless defined $options{format};
    confess "Logger required"          unless defined $options{logger};
    confess "Database handle required" unless defined $options{dbh};

    $options{format} = lc $options{format};

    my $shredder_class = $class . '::' . ucfirst $options{format};

    if ( !eval("require $shredder_class") ) {
        confess "Unknown input format '$options{format}' specified.";
    }

    my $self = {%options};

    return bless $self, $shredder_class;
}

sub host {
    my ($self) = @_;
    return $self->{host};
}

sub set_host {
    my ( $self, $host ) = @_;
    $self->{host} = $host;
}

sub has_host {
    my ($self) = @_;
    return defined $self->{host};
}

sub format {
    my ($self) = @_;
    return $self->{format};
}

sub logger {
    my ($self) = @_;
    return $self->{logger};
}

sub dbh {
    my ($self) = @_;
    return $self->{dbh};
}

sub shred_directory {
    my ( $self, $dir ) = @_;

    $self->logger->info( "Shredding files for host: " . $self->host() )
        if $self->has_host();

    $self->logger->info("Shredding directory: $dir");

    confess "Cannot access '$dir': No such directory" unless -d $dir;

    my @files;
    if ( my $date = $self->get_event_max_date() ) {
        $self->logger->info("Shredding files dated after $date.");
        @files = @{ $self->get_file_names( $dir, $date ) };
    }
    else {
        $self->logger->info('Empty database, shredding all files.');
        @files = @{ $self->get_file_names($dir) };
    }

    # Prepend directory path
    @files = map { File::Spec->catfile( $dir, $_ ) } @files;

    return $self->shred_files( \@files );
}

sub shred_files {
    my ( $self, $files ) = @_;

    my $record_count = 0;
    my $file_count   = 0;

    for my $file (@$files) {
        if ( !-e $file ) {
            $self->logger->warn("File not found '$file'.");
            next;
        }
        if ( !-f $file ) {
            $self->logger->warn("Skipping non-file '$file'.");
            next;
        }
        if ( !-r $file ) {
            $self->logger->warn("Skipping unreadable file '$file'.");
            next;
        }

        $record_count += $self->shred_file($file);
        $file_count++;
    }

    $self->logger->info("Shredded $file_count files.");

    return $record_count;
}

sub shred_file {
    my ( $self, $file ) = @_;

    $self->logger->info("Processing file: $file");

    confess "Cannot access '$file': No such file" unless -f $file;

    my $fh;
    if ( !open $fh, '<', $file ) {
        $self->logger->fatal("Could not open file '$file': $!");
        exit 1;
    }

    return $self->shred_fh($fh);
}

sub shred_fh {
    my ( $self, $fh ) = @_;

    my $count = 0;
    while ( defined( my $line = readline($fh) ) ) {
        chomp $line;

        my $event = eval { $self->shred_line($line); };
        if ($@) {
            $self->logger->fatal($@);
            next;
        }

        # Skip empty events
        next unless %$event;

        my ( $sql, @values ) = $self->get_insert_query($event);

        my $sth = $self->dbh->prepare($sql);
        $sth->execute(@values);

        $count++;
    }

    $self->logger->info("Shredded $count records.");

    return $count;
}

sub shred_line {
    die "Shredder subclass must implement shred_line";
}

sub get_event_table {
    my ($self) = @_;
    return $self->format . '_event';
}

sub get_insert_query {
    my ( $self, $event ) = @_;

    my $table = $self->get_event_table();

    my $sql = qq[ INSERT INTO $table SET ];

    my @pairs = map {qq[ `$_` = ? ]} keys %$event;

    $sql .= join( ',', @pairs );

    return ( $sql, values %$event );
}

sub get_transform_map {
    die "Shredder subclass must implement get_transform_map\n";
}

sub get_transform_query {
    my ($self) = @_;

    my %map = %{ $self->get_transform_map };

    $map{source_format} = "'" . $self->format() . "'";

    my @columns = map {qq[`$_`]} keys %map;
    my $columns     = join ', ', @columns;
    my $select_expr = join ', ', values %map;

    my $source_table = $self->get_event_table();

    my $sql = qq{
        INSERT INTO event ($columns)
        SELECT $select_expr
        FROM $source_table
    };

    return $sql;
}

sub get_event_max_date {
    my ($self) = @_;

    my $sql = q{
        SELECT DATE_FORMAT( MAX(date_key), '%Y-%m-%d' )
        FROM event
    };

    if ( $self->has_host() ) {
        $sql .= " WHERE cluster = " . $self->dbh->quote( $self->host() );
    }

    return $self->dbh->selectrow_arrayref($sql)->[0];
}

sub get_file_names {
    my ( $self, $dir, $date ) = @_;

    my $today = DateTime->now( time_zone => 'local' );
    $today->set(
        hour   => 0,
        minute => 0,
        second => 0,
    );

    if ( !defined $date ) {

        # If no date is specified return all files in the directory
        # excluding the file for today.

        my $dh;
        if ( !opendir $dh, $dir ) {
            $self->logger->fatal("Could not open dir '$dir': $!");
            exit 1;
        }
        my @files = sort readdir($dh);

        # Skip hidden files
        @files = grep { !/^\./ } @files;

        # Remove file for today
        my $today_file = $today->strftime('%Y%m%d');
        @files = grep { $_ ne $today_file } @files;

        return \@files;
    }
    elsif ( $date =~ /^(\d{4})-(\d{2})-(\d{2})$/ ) {

        # If a date is specified return all files in the directory dated
        # after that date and before today.

        my $current = DateTime->new(
            year  => $1,
            month => $2,
            day   => $3,
        );
        $current->add( days => 1 );

        my @files;

        while ( DateTime->compare( $current, $today ) < 0 ) {
            push @files, $current->strftime('%Y%m%d');
            $current->add( days => 1 );
        }

        # Remove nonexistent files
        @files = grep { -f File::Spec->catfile( $dir, $_ ) } @files;

        return \@files;
    }
    else {
        confess "Invalid date format: '$date'";
    }
}

sub transform_data {
    my ($self) = @_;

    # XXX move this somewhere else or limit the events that are
    # transformed by the query below
    $self->dbh->do( q{ DELETE FROM event WHERE source_format = ? },
        undef, $self->format() );

    my $sql = $self->get_transform_query();

    return $self->dbh->do($sql);
}

1;

__END__

=head1 NAME

Ubmod::Shredder - Abstract base class for accounting log shredders.

=head1 VERSION

Version: $Id$

=head1 SYNOPSIS

  package Ubmod::Shredder::Myformat;

  use base qw(Ubmod::Shredder);

  sub shred_line {
      my ( $self, $line ) = @_;

      my %event;

      # Parse $line and put results in %event.

      return \%event;
  }

  sub get_event_table {

      # Return the name of the custom event table.
      return 'myformat_event';
  }

  sub get_insert_query {
      my ($self, $event) = @_;

      # Construct a SQL query to insert data into the custom event
      # table.  Return the query with optional bind params.

      return ( $sql, @values );
  }

  sub get_transform_query {
      my ($self) = @_;

      # Construct a SQL query to insert data from the custom event table
      # to the generic event table.

      return $sql;
  }

  1;

Then specify the format when shredding:

  $ ubmod-shredder -s --format myformat -i /path/to/mylogfile

=head1 DESCRIPTION

Abstract base class. Resource manager shredders should extend this
class.

=head1 METHODS

=head2 host()

Returns the hostname if it has been set. This should be used by the
shred implementation to replace the cluster name.

=head2 set_host( $host )

Set the hostname to use. This is typically set by the -H option of the
frontend script.

=head2 has_host()

Check if the hostname has been set. This should be used in the shred
implementation to check if the cluster name should be replaced.

=head2 logger()

Returns the logger object. This should be used to log any messages.

=head2 shred_line( $line )

This must be implemented by subclasses.

Shred the given C<$line>. Returns a hash reference containing the
information parsed from the line. Should C<die> if there was an error
parsing the line. Keys in the returned hash reference should match the
columns of the custom event table.

=head2 get_event_table()

This must be implemented by subclasses.

Returns the name of the custom event table used by this shredder.

=head2 get_insert_query( $event )

This may be overridden by subclasses.  A default version is supplied
that insert every value in the C<$event> hash to the custom event table
using the keys of the hash as column names.

Returns a list containing the SQL query that will insert data from
C<$event> into the custom event table followed by a list of bind
parameters.

=head2 get_transform_query()

This must be implemented by subclasses.

Returns a SQL query that will insert data from the custom event table
into the generic event table.

e.g.

  INSERT INTO event ( ... ) SELECT ... FROM myformat_event

=back

=head1 AUTHOR

Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>

=head1 COPYRIGHT AND LICENSE

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The Original Code is UBMoD.

The Initial Developer of the Original Code is Research Foundation of State
University of New York, on behalf of University at Buffalo.

Portions created by the Initial Developer are Copyright (C) 2007 Research
Foundation of State University of New York, on behalf of University at
Buffalo.  All Rights Reserved.

=cut

