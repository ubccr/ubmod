package Ubmod::Logger;
use strict;
use warnings;
use DateTime;

sub new {
    my ( $class, $verbose ) = @_;
    my $self = { verbose => $verbose };
    return bless $self, $class;
}

sub info {
    my ( $self, $message ) = @_;
    $self->_log( $message, 'info' ) if $self->{verbose};
}

sub warn {
    my ( $self, $message ) = @_;
    $self->_log( $message, 'warn' );
}

sub fatal {
    my ( $self, $message ) = @_;
    $self->_log( $message, 'fatal' );
}

sub _log {
    my ( $self, $message, $level ) = @_;
    my $dt = DateTime->now();
    print $dt->ymd('-') . ' ' . $dt->hms(':') . " [$level] $message\n";
}

1;

__END__

=head1 NAME

Ubmod::Logger - UBMoD logging module

=head1 VERSION

Version: $Id$

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
