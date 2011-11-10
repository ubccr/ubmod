package Ubmod::Shredder::Pbs;
use strict;
use warnings;

use base qw(Ubmod::BaseShredder);

my $pattern = qr|
    ^
    (\d{2}/\d{2}/\d{4}\s\d{2}:\d{2}:\d{2})    # Date and time
    ;
    (\w)                                      # Event type
    ;
    ([^;]+)                                   # Job ID
    ;
    (.*)                                      # Params
|x;

# These entries need to be parsed and formatted
my %formats = (
    resources_used_vmem     => 'memory',
    resources_used_mem      => 'memory',
    resources_used_walltime => 'time',
    resources_used_cput     => 'time',
    resource_list_pcput     => 'time',
    resource_list_cput      => 'time',
    resource_list_walltime  => 'time',
    resource_list_mem       => 'memory',
    resource_list_pmem      => 'memory',
);

# Mapping from generic event table to PBS specific event table
my %map = (
    date_key        => 'date_key',
    job_id          => 'job_id',
    job_array_index => 'job_array_index',
    job_name        => 'jobname',
    cluster         => 'LOWER(host)',
    queue           => 'LOWER(queue)',
    user            => 'LOWER(user)',
    group           => 'LOWER(`group`)',
    account         => 'account',
    start_time      => 'FROM_UNIXTIME(start)',
    end_time        => 'FROM_UNIXTIME(end)',
    submission_time => 'FROM_UNIXTIME(ctime)',
    wallt           => 'resources_used_walltime * resources_used_cpus',
    cput            => 'resources_used_cput',
    mem             => 'resources_used_mem',
    vmem            => 'resources_used_vmem',
    wait            => 'GREATEST(start - ctime, 0)',
    exect           => 'GREATEST(end - start, 0)',
    nodes           => 'resources_used_nodes',
    cpus            => 'resources_used_cpus',
);

sub new {
    my ($class) = @_;
    my $self = $class->SUPER::new();
    return bless $self, $class;
}

sub shred {
    my ( $self, $line ) = @_;

    my ( $date, $type, $job_id, $params );

    if ( $line =~ $pattern ) {
        ( $date, $type, $job_id, $params ) = ( $1, $2, $3, $4 );
    }
    else {
        die "Malformed PBS acct line: $line";
    }

    $date =~ s#^(\d{2})/(\d{2})/(\d{4})#$3-$1-$2#;

    my $event = {
        date_key => $date,
        type     => $type,
    };

    $self->_set_job_id_and_host( $event, $job_id );

    $event->{host} = $self->host() if $self->has_host();

    my @parts = split /\s+/, $params;

    foreach my $part (@parts) {
        # Skip parts that aren't attributes.
        next unless $part =~ /=/;

        my ( $key, $value ) = split /=/, $part, 2;

        $key =~ s/\./_/g;
        $key = lc $key;

        if ( $key eq 'exec_host' ) {
            $self->_set_exec_host( $event, $value );
        }
        elsif ( defined( $formats{$key} ) ) {
            my $parser = '_parse_' . $formats{$key};
            $event->{$key} = $self->$parser($value);
        }
        else {
            $event->{$key} = $value;
        }
    }

    return $event;
}

sub get_transform_query {
    my ($self) = @_;

    my @columns = map {qq[`$_`]} keys %map;
    my $columns     = join( ',', @columns );
    my $select_expr = join( ',', values %map );

    my $sql = qq{
        INSERT INTO event ( $columns )
        SELECT $select_expr
        FROM pbs_event
        WHERE type = 'E'
    };

    return $sql;
}

sub _parse_time {
    my ( $self, $time ) = @_;

    my ( $h, $m, $s ) = split /:/, $time;
    return $h * 60 * 60 + $m * 60 + $s;
}

sub _parse_memory {
    my ( $self, $memory ) = @_;

    if ( $memory =~ /^\d+$/ ) {
        return $self->_scale_memory( $memory, 'kb' );
    }
    elsif ( $memory =~ /^(\d+)(\D+)$/ ) {
        return $self->_scale_memory( $1, $2 );
    }
    else {
        die "Unknown memory format: $memory";
    }
}

# Scale from the given unit to KiB
sub _scale_memory {
    my ( $self, $value, $unit ) = @_;

    return $value / 1024        if $unit eq 'b';
    return $value               if $unit eq 'kb';
    return $value * 1024        if $unit eq 'mb';
    return $value * 1024 * 1024 if $unit eq 'gb';

    die "Unknown memory unit: $unit";
}

sub _set_job_id_and_host {
    my ( $self, $event, $job_id ) = @_;

    my ( $job, $host ) = split /\./, $job_id, 2;
    my ( $id, $index ) = split /-/, $job;

    $event->{host}            = $host;
    $event->{job_id}          = $id;
    $event->{job_array_index} = $index;
}

sub _set_exec_host {
    my ( $self, $event, $hosts_str ) = @_;

    my $cpus  = 0;
    my $nodes = 0;

    my $hosts = $self->_parse_hosts($hosts_str);

    my %host_map;
    foreach my $host (@$hosts) {
        $host_map{ $host->{host} } += 1;
    }

    while ( my ( $host, $total ) = each(%host_map) ) {
        $nodes++;
        $cpus += $total;
    }

    $event->{resources_used_nodes} = $nodes;
    $event->{resources_used_cpus}  = $cpus;
}

sub _parse_hosts {
    my ( $self, $hosts ) = @_;

    my @parts = split /\+/, $hosts;

    my @hosts;
    foreach my $part (@parts) {
        my ( $host, $cpu ) = split /\//, $part;
        push @hosts,
            {
            host => $host,
            cpu  => $cpu,
            };
    }

    return \@hosts;
}

1;

__END__

=head1 NAME

Ubmod::Shredder::Pbs - Parse PBS/TORQUE format accounting logs

=head1 VERSION

Version: $Id$

=head1 SYNOPSIS

    my $shredder = Ubmod::Shredder::Pbs->new();
    my $event    = $shredder->shred($line);

=head1 DESCRIPTION

This module parses PBS/TORQUE accounting log files.  It defines a class
that implements a single public method to do so.

=head1 CONSTRUCTOR

=head2 new()

    my $shredder = Ubmod::Shredder::Pbs->new();

=head1 METHOD

=head2 shred($line)

    my $event = $shredder->shred($line);

Shred the given C<$line>.  Returns a hashref containing the information
parsed from the line.  C<die>s if there was an error parsing the line.

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
