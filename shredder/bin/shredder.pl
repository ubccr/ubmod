#!/usr/bin/env perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../lib";
use DBI;
use Getopt::Long;
use Pod::Usage;
use File::Spec;
use Config::Tiny;
use DateTime;
use Ubmod::Aggregator;
use Ubmod::Logger;

# Global variables
my $Dbh;
my $Logger;
my $Format;

sub main {
    my ($stdio,  $file,     $dir,    $host,    $shred,
        $format, $end_date, $update, $verbose, $help
    );

    Getopt::Long::Configure('no_ignore_case');

    my $result = GetOptions(
        ''             => \$stdio,
        'in|i=s'       => \$file,
        'dir|d=s'      => \$dir,
        'host|H=s'     => \$host,
        'shred|s'      => \$shred,
        'format|f=s'   => \$format,
        'end-date|e=s' => \$end_date,
        'update|u'     => \$update,
        'verbose|v'    => \$verbose,
        'help|h'       => \$help,
    );

    if ($help) {
        pod2usage( -exitval => 0, -verbose => 2 );
    }

    $Logger = Ubmod::Logger->new($verbose);

    my $config = Config::Tiny->read("$FindBin::Bin/../config/settings.ini");
    db_connect( @{ $config->{database} }{qw( host dbname user password )} );

    if ($shred) {

        if ( !$format ) {
            $Logger->fatal("No input format specified.");
            exit 1;
        }
        elsif ( $format !~ /^\w+$/ ) {
            $Logger->fatal("Invalid input format specified.");
            exit 1;
        }

        $Format = lc $format;
        my $shredder_class = 'Ubmod::Shredder::' . ucfirst $Format;

        if ( !eval("require $shredder_class") ) {
            $Logger->fatal("Unknown input format specified.");
            exit 1;
        }

        my $shredder = $shredder_class->new();

        $shredder->set_host($host) if defined $host;

        $Logger->info("Shredding.");

        my $count;
        if ($dir) {
            $count = process_directory( $shredder, $dir );
        }
        elsif ($file) {
            $count = process_file( $shredder, $file );
        }
        elsif ($stdio) {
            $Logger->info("Processing standard input.");
            $count = process_fh( $shredder, *STDIN );
        }
        else {
            $Logger->fatal("No input source specified.");
            pod2usage( -exitval => 1, -verbose => 1 );
        }

        $Logger->info("Total shredded: $count");
        $Logger->info("Done shredding!");

        transform_data($shredder);
    }

    if ($update) {
        $Logger->info("Updating aggregate tables.");

        my %options = ( dbh => $Dbh, logger => $Logger );

        if ( defined $end_date ) {
            if ( $end_date =~ /^(\d{4})-(\d{1,2})-(\d{1,2})$/ ) {
                my $date = eval {
                    DateTime->new( year => $1, month => $2, day => $3 );
                };
                if ( !$date ) {
                    $Logger->fatal("Invalid date: '$end_date'");
                    exit 1;
                }
                $options{end_date} = $date;
            }
            else {
                $Logger->fatal("Invalid date format: '$end_date'");
                exit 1;
            }
        }

        my $aggregator = Ubmod::Aggregator->new(%options);
        $aggregator->aggregate();

        $Logger->info("Done updating aggregate tables!");
    }

    if ( !$update && !$shred ) {
        $Logger->fatal('No shredding or updating option was specified');
        pod2usage( -exitval => 1, -verbose => 1 );
    }
}

sub process_directory {
    my ( $shredder, $dir ) = @_;

    $Logger->info("Processing directory: $dir");

    if ( !-d $dir ) {
        $Logger->fatal("Cannot access '$dir': No such directory");
        exit 1;
    }

    my @files;
    if ( my $date = get_event_max_date() ) {
        $Logger->info("Shredding files dated after $date.");
        @files = @{ get_file_names($date) };
    }
    else {
        $Logger->info('Empty database, shredding all files.');

        my $dh;
        if ( !opendir $dh, $dir ) {
            $Logger->fatal("Could not open dir '$dir': $!");
            exit 1;
        }
        @files = sort readdir($dh);
    }

    # Skip hidden files
    @files = grep { !/^\./ } @files;

    # Prepend directory path
    @files = map { File::Spec->catfile( $dir, $_ ) } @files;

    return process_files( $shredder, \@files );
}

sub process_files {
    my ( $shredder, $files ) = @_;

    my $record_count = 0;
    my $file_count   = 0;

    foreach my $file (@$files) {
        if ( !-e $file ) {
            $Logger->warn("File not found '$file'.");
            next;
        }
        if ( !-f $file ) {
            $Logger->warn("Skipping non-file '$file'.");
            next;
        }
        if ( !-r $file ) {
            $Logger->warn("Skipping unreadable file '$file'.");
            next;
        }

        $record_count += process_file( $shredder, $file );
        $file_count++;
    }

    $Logger->info("Shredded $file_count files.");

    return $record_count;
}

sub process_file {
    my ( $shredder, $file ) = @_;

    $Logger->info("Processing file: $file");

    if ( !-f $file ) {
        $Logger->fatal("Cannot access '$file': No such file");
        exit 1;
    }

    my $fh;
    if ( !open $fh, '<', $file ) {
        $Logger->fatal("Could not open file '$file': $!");
        exit 1;
    }

    return process_fh( $shredder, $fh );
}

sub process_fh {
    my ( $shredder, $fh ) = @_;

    my $count = 0;
    while ( defined( my $line = readline($fh) ) ) {
        my $event = eval { $shredder->shred($line); };
        if ($@) {
            $Logger->fatal($@);
            next;
        }

        # Skip empty events
        next unless %$event;

        insert_native_event($event);
        $count++;
    }

    $Logger->info("Shredded $count records.");

    return $count;
}

sub db_connect {
    my ( $host, $dbname, $user, $pass ) = @_;
    my $dsn = "DBI:mysql:host=$host;database=$dbname";
    $Dbh = DBI->connect( $dsn, $user, $pass );
}

sub insert_native_event {
    my ($event) = @_;

    my $sql = qq[ INSERT INTO ${Format}_event SET ];
    my @pairs = map {qq[ `$_` = ? ]} keys %$event;
    $sql .= join( ',', @pairs );
    my $sth = $Dbh->prepare($sql);
    $sth->execute( values %$event );

    return $Dbh->{mysql_insertid};
}

sub insert_host_log {
    my ($log) = @_;
    my $sth = $Dbh->prepare(
        q{
            INSERT INTO host_log SET
                event_id = ?,
                host = ?,
                cpu = ?
        }
    );
    $sth->execute( @$log{qw( event_id host cpu )} );
}

sub get_event_max_date {
    my $sql = q{
        SELECT DATE_FORMAT( MAX(date_key), '%Y-%m-%d' )
        FROM event
    };
    return $Dbh->selectrow_arrayref($sql)->[0];
}

sub get_file_names {
    my ($date) = @_;

    my $today = DateTime->now();
    $today->set(
        hour   => 0,
        minute => 0,
        second => 0,
    );

    my $current;
    if ( $date =~ /^(\d{4})-(\d{2})-(\d{2})$/ ) {
        $current = DateTime->new(
            year  => $1,
            month => $2,
            day   => $3,
        );
    }
    else {
        $Logger->fatal("Invalid date format: '$date'");
        exit 1;
    }

    my @files;

    while ( DateTime->compare( $current, $today ) < 0 ) {
        $current->add( days => 1 );
        push @files, $current->strftime('%Y%m%d');
    }

    return \@files;
}

sub transform_data {
    my ($shredder) = @_;

    # XXX move this somewhere else or limit the events that are
    # transformed by the query below
    $Dbh->do(q{ TRUNCATE event });

    my $sql = $shredder->get_transform_query();
    return $Dbh->do($sql);
}

main(@ARGV);

__END__

=head1 NAME

shredder.pl - UBMoD shredder script

=head1 VERSION

Version: $Id$

=head1 SYNOPSIS

B<shredder.pl> [B<-v>] [B<-u>] [B<-s>] [B<-H> I<host>] [B<-i> I<file>|B<-d> I<dir>]

=head1 OPTIONS

=head2 GENERAL OPTIONS

=over 8

=item B<-s>, B<--shred>

Shred accounting file(s).

=item B<-u>, B<--update>

Update aggregate tables.

=item B<-v>, B<--verbose>

Increase verbosity.

=item B<-h>, B<--help>

Display this text and exit.

=back

=head2 SHREDDING OPTIONS

These options may be used with the C<-s> or C<--shred> option.

=over 8

=item B<-f>, B<--format> I<format>

Specify accounting file format (pbs or sge).

=item B<-H>, B<--host> I<hostname>

Explicitly set host from which the log file(s) originated.

=item B<-i>, B<--in> I<file>

Specify input file.

=item B<-d>, B<--dir> I<directory>

Specify accounting log directory.

=item B<->

A lone dash C<-> indicates standard input should be used.

=back

=head2 AGGREGATION OPTIONS

These options may be used with the C<-u> or C<--update> option.

=over 8

=item B<-e>, B<--end-date> I<date>

Explicitly set the end date used for aggregation time intervals. The
date must be in YYYY-MM-DD format. Defaults to yesterday.

=back

=head1 DESCRIPTION

This script can be used to parse and aggregate accounting data for use
with the UBMoD portal.

=head1 EXAMPLES

  shredder.pl -s --format pbs -d /var/spool/pbs/server_priv/accounting

  shredder.pl -s --format sge -i /var/lib/gridengine/default/common/accounting

  shredder.pl -u

  shredder.pl -h

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
