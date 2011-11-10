package Ubmod::BaseShredder;
use strict;
use warnings;

sub new {
    my ($class) = @_;
    my $self = {};
    return bless $self, $class;
}

sub set_host {
    my ( $self, $host ) = @_;
    $self->{host} = $host;
}

sub get_host {
    my ($self) = @_;
    return $self->{host};
}

sub has_host {
    my ($self) = @_;
    return exists $self->{host};
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

  sub new {
      my ($class) = @_;
      my $self = $class->SUPER::new();
      return bless $self, $class;
  }

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

  $ shredder.pl -s --format myformat -i /path/to/mylogfile

=head1 DESCRIPTION

Abstract base class. Resource manager shredders should extend this
class.

=head1 METHODS

=head2 new()

Default constructor.

=head2 set_host( $host )

Set the hostname to use. This is typically set by the -H option of the
frontend script.

=head2 get_host()

Returns the hostname if it has been set. This should be used by the
shred implementation to replace the cluster name.

=head2 has_host()

Check if the hostname has been set. This should be used in the shred
implementation to check if the cluster name should be replaced.

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

The contents of this file are subject to the University at Buffalo Public
License Version 1.0 (the "License"); you may not use this file except in
compliance with the License. You may obtain a copy of the License at
http://www.ccr.buffalo.edu/licenses/ubpl.txt

Software distributed under the License is distributed on an "AS IS" basis,
WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
the specific language governing rights and limitations under the License.

The Original Code is UBMoD.

The Initial Developer of the Original Code is Research Foundation of State
University of New York, on behalf of University at Buffalo.

Portions created by the Initial Developer are Copyright (C) 2007 Research
Foundation of State University of New York, on behalf of University at
Buffalo.  All Rights Reserved.

Alternatively, the contents of this file may be used under the terms of
either the GNU General Public License Version 2 (the "GPL"), or the GNU
Lesser General Public License Version 2.1 (the "LGPL"), in which case the
provisions of the GPL or the LGPL are applicable instead of those above. If
you wish to allow use of your version of this file only under the terms of
either the GPL or the LGPL, and not to allow others to use your version of
this file under the terms of the UBPL, indicate your decision by deleting
the provisions above and replace them with the notice and other provisions
required by the GPL or the LGPL. If you do not delete the provisions above,
a recipient may use your version of this file under the terms of any one of
the UBPL, the GPL or the LGPL.

=cut
