package Ubmod::BaseShredder;
use strict;
use warnings;

sub new {
    my ( $class, %options ) = @_;

    die "logger required" unless defined $options{logger};

    my $self = {%options};

    return bless $self, $class;
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

sub logger {
    my ($self) = @_;
    return $self->{logger};
}

sub shred {
    die "Shredder subclass must implement shred\n";
}

sub get_transform_query {
    die "Shredder subclass must implement get_transform_query\n";
}

1;

__END__

=head1 NAME

Ubmod::BaseShredder - Abstract base class for accounting log shredders.

=head1 VERSION

Version: $Id$

=head1 SYNOPSIS

  package Ubmod::Shredder::Myformat;

  use base qw(Ubmod::BaseShredder);

  sub shred {
      my ( $self, $line ) = @_;

      my %event;

      # Parse $line and put results in %event.

      return \%event;
  }

  sub get_transform_query {
      my ($self) = @_;

      # Construct a SQL query to insert data from the custom event
      # table to the generic event table.

      return $sql;
  }

  1;

Then specify the format when shredding:

  $ ubmod-shredder -s --format myformat -i /path/to/mylogfile

=head1 DESCRIPTION

Abstract base class. Resource manager shredders should extend this
class.

=head1 METHODS

=head2 new( logger => $logger )

Default constructor.

The C<$logger> parameter is required and should be an instance of
C<Ubmod::Logger>.

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

=head2 shred($line)

This must be implemented by subclasses.

Shred the given C<$line>. Returns a hash reference containing the
information parsed from the line. Should C<die> if there was an error
parsing the line. Keys in the returned hash reference should match the
columns of the custom event table.

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
