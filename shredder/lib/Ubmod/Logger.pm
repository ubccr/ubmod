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
