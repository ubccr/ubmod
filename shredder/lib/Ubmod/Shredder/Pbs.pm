package Ubmod::Shredder::Pbs;
use strict;
use warnings;

use base qw(Ubmod::Shredder);

my $pattern = qr|
    ^
    (
        \d{2}/\d{2}/\d{4}    # Date
        \s
        \d{2}:\d{2}:\d{2}    # Time
    )
    ;
    ( \w )                   # Event type
    ;
    ( [^;]+ )                # Job ID
    ;
    ( .* )                   # Params
|x;

# All the columns in the pbs_event table, excluding the primary key
my @columns = qw(
    date_key
    job_id
    job_array_index
    host
    queue
    user
    group
    ctime
    qtime
    start
    end
    etime
    exit_status
    session
    requestor
    jobname
    owner
    account
    session_id
    error_path
    output_path
    exec_host
    resources_used_vmem
    resources_used_mem
    resources_used_walltime
    resources_used_nodes
    resources_used_cpus
    resources_used_cput
    resource_list_nodes
    resource_list_procs
    resource_list_neednodes
    resource_list_pcput
    resource_list_cput
    resource_list_walltime
    resource_list_ncpus
    resource_list_nodect
    resource_list_mem
    resource_list_pmem
);

# Use this hash to check if a column exists
my %columns = map { $_ => 1 } @columns;

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

sub shred_line {
    my ( $self, $line ) = @_;

    my ( $date, $type, $job_id, $params );

    if ( $line =~ $pattern ) {
        ( $date, $type, $job_id, $params ) = ( $1, $2, $3, $4 );
    }
    else {
        die "Malformed PBS acct line: $line";
    }

    # Ignore all non "end" events.
    return {} unless $type eq 'E';

    $date =~ s#^(\d{2})/(\d{2})/(\d{4})#$3-$1-$2#;

    my $event = {
        date_key => $date,
    };

    $self->_set_job_id_and_host( $event, $job_id );

    $event->{host} = $self->host() if $self->has_host();

    my @parts = split /\s+/, $params;

    for my $part (@parts) {

        # Skip parts that aren't attributes.
        next unless $part =~ /=/;

        my ( $key, $value ) = split /=/, $part, 2;

        $key =~ s/\./_/g;
        $key = lc $key;

        if ( $key eq 'exec_host' ) {
            $self->_set_exec_host( \%event, $value );
        }
        elsif ( defined( $formats{$key} ) ) {
            my $parser = '_parse_' . $formats{$key};
            $event{$key} = $self->$parser($value);
        }
        else {
            $event{$key} = $value;
        }
    }

    for my $key ( keys %event ) {
        if ( !exists $columns{$key} ) {
            $self->logger->warn("Ignoring unknown attribute '$key'");
            delete $event{$key};
        }
    }

    return \%event;
}

sub get_transform_map { return \%map; }

sub _parse_time {
    my ( $self, $time ) = @_;

    my ( $h, $m, $s ) = split /:/, $time;
    return $h * 60 * 60 + $m * 60 + $s;
}

sub _parse_memory {
    my ( $self, $memory ) = @_;

    if ( $memory =~ /^(\d*\.?\d+)(\D+)?$/ ) {
        my ( $value, $unit ) = ( $1, $2 );

        # PBS uses kilobytes by default
        $unit ||= 'kb';
        return $self->_scale_memory( $value, $unit );
    }
    else {
        die "Unknown memory format: $memory";
    }
}

# Scale from the given unit to KiB
sub _scale_memory {
    my ( $self, $value, $unit ) = @_;

    my $bytes;

    return int( $value / 1024 )        if $unit eq 'b';
    return int($value)                 if $unit eq 'kb';
    return int( $value * 1024 )        if $unit eq 'mb';
    return int( $value * 1024 * 1024 ) if $unit eq 'gb';

    die "Unknown memory unit: $unit";
}

sub _set_job_id_and_host {
    my ( $self, $event, $id_string ) = @_;

    # id_string is formatted as "sequence_number.hostname".
    my ( $sequence, $host ) = split /\./, $id_string, 2;

    my ( $job_id, $index );

    # If the job is part of a job array the sequence number may be
    # formatted as "job_id[array_index]" or "job_id-array_index". If the
    # sequence number represents the entire job array it may be
    # formatted as "job_id[]".
    if ( $sequence =~ / ^ (\d+) \[ (\d+)? \] $ /x ) {
        $job_id = $1;
        $index  = $2;
    }
    elsif ( $sequence =~ / ^ (\d+) - (\d+) $ /x ) {
        $job_id = $1;
        $index  = $2;
    }
    elsif ( $sequence =~ / ^ \d+ $ /x ) {
        $job_id = $sequence;
    }
    else {
        $self->logger->warn("Unknown id_string format: '$id_string'");
        $job_id = $sequence;
    }

    $event->{host}            = $host;
    $event->{job_id}          = $job_id;
    $event->{job_array_index} = $index;
}

sub _set_exec_host {
    my ( $self, $event, $hosts_str ) = @_;

    my $cpus  = 0;
    my $nodes = 0;

    my $hosts = $self->_parse_hosts($hosts_str);

    my %host_map;
    for my $host (@$hosts) {
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
    for my $part (@parts) {
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

=head1 DESCRIPTION

This module parses PBS/TORQUE accounting log files.

See C<Ubmod::Shredder>.

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

