package Ubmod::Logger;
use strict;
use warnings;
use DateTime;

sub new {
    my ( $class, $verbose ) = @_;
    my $self = { verbose => $verbose };
    return bless $self, $class;
}

sub log {
    my ( $self, $message, $level ) = @_;
    print DateTime->now()->iso8601() . " [$level] $message\n";
}

sub info {
    my ( $self, $message ) = @_;
    $self->log($message, 'info') if $self->{verbose};
}

sub warn {
    my ( $self, $message ) = @_;
    $self->log($message, 'warn');
}

sub fatal {
    my ( $self, $message ) = @_;
    $self->log($message, 'fatal');
}

1;


__END__

=head1 NAME

Ubmod::Logger - UBMoD logging module

=head1 SYNOPSIS

    my $logger = Ubmod::Logger->new($verbose);
    $logger->info('Message.');
    $logger->warn('Warning!');
    $logger->fatal('Fatal Error!!!');

=head1 DESCRIPTION

This module implements a simple logging mechanism (printing to STOUT).

=head1 CONSTRUCTOR

=head2 new()

=head2 new($verbose)

    my $logger = Ubmod::Logger->new($verbose);

Use a true value for $verbose to indicate that all messages should be
sent to standard out.  Otherwise, only C<warn> and C<fatal> messages
will be output.

=head1 METHODS

=head2 info()

    $logger->info('Message.');

Log a message.  The message is only output when the
verbose option is set.

=head2 warn()

    $logger->warn('Warning!');

Log a warning.

=head2 fatal()

    $logger->fatal('Fatal Error!!!');

Log a fatal error.

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
