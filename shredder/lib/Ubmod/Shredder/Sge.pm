package Ubmod::Shredder::Sge;
use strict;
use warnings;

use base qw(Ubmod::BaseShredder);

# Entries in the accouting log file
my @entry_names = qw(
    qname
    hostname
    group
    owner
    job_name
    job_number
    account
    priority
    submission_time
    start_time
    end_time
    failed
    exit_status
    ru_wallclock
    ru_utime
    ru_stime
    ru_maxrss
    ru_ixrss
    ru_ismrss
    ru_idrss
    ru_isrss
    ru_minflt
    ru_majflt
    ru_nswap
    ru_inblock
    ru_oublock
    ru_msgsnd
    ru_msgrcv
    ru_nsignals
    ru_nvcsw
    ru_nivcsw
    project
    department
    granted_pe
    slots
    task_number
    cpu
    mem
    io
    category
    iow
    pe_taskid
    maxvmem
    arid
    ar_submission_time
);

my @resource_attributes = qw(
    arch
    qname
    hostname
    notify
    calendar
    min_cpu_interval
    tmpdir
    seq_no
    s_rt
    h_rt
    s_cpu
    h_cpu
    s_data
    h_data
    s_stack
    h_stack
    s_core
    h_core
    s_rss
    h_rss
    slots
    s_vmem
    h_vmem
    s_fsize
    h_fsize
    num_proc
    mem_free
);

# Use this hash to check if a resource exists
my %resource_attributes = map { $_ => 1 } @resource_attributes;

# These resource attributes may require formatting
my %attribute_formats = (
    s_data   => 'memory',
    h_data   => 'memory',
    s_stack  => 'memory',
    h_stack  => 'memory',
    s_core   => 'memory',
    h_core   => 'memory',
    s_rss    => 'memory',
    h_rss    => 'memory',
    s_vmem   => 'memory',
    h_vmem   => 'memory',
    s_fsize  => 'memory',
    h_fsize  => 'memory',
    mem_free => 'memory',
);

# Mapping from generic event table to SGE specific event table
my %map = (
    date_key        => 'FROM_UNIXTIME(MAX(end_time))',
    job_id          => 'job_number',
    job_array_index => 'task_number',
    job_name        => 'job_name',
    cluster         => 'cluster',
    queue           => 'qname',
    user            => 'owner',
    group           => q{`group`},
    account         => 'account',
    project         => 'project',
    start_time      => 'FROM_UNIXTIME(MIN(start_time))',
    end_time        => 'FROM_UNIXTIME(MAX(end_time))',
    submission_time => 'FROM_UNIXTIME(MIN(submission_time))',
    wallt           => 'AVG(ru_wallclock) * slots',
    cput            => 'SUM(cpu)',
    mem             => 'SUM(mem * 1024 * 1024 / cpu)',
    vmem            => 'SUM(maxvmem) / 1024',
    wait            => 'GREATEST(start_time - submission_time, 0)',
    exect           => 'GREATEST(end_time - start_time, 0)',
    nodes           => 'COUNT(DISTINCT hostname)',
    cpus =>
        'GREATEST(COALESCE(slots, 1), COALESCE(resource_list_num_proc, 1))',
);

sub shred {
    my ( $self, $line ) = @_;

    # Ignore comments
    return {} if $line =~ /^#/;

    # Ignore lines that contain only one character or are blank
    return {} if length($line) <= 1;

    my @entries = split /:/, $line;

    if ( scalar @entries != scalar @entry_names ) {
        die "Malformed SGE acct line: $line";
    }

    my %event
        = map { $entry_names[$_] => $entries[$_] } 0 .. scalar @entries - 1;

    $self->_set_resource_lists( \%event, $event{category} );

    $event{cluster} = $self->host();

    return \%event;
}

sub get_event_table { return 'sge_event'; }

sub get_insert_query {
    my ( $self, $event ) = @_;

    my ( $sql, @values ) = $self->SUPER::get_insert_query($event);

    $sql .= ' ON DUPLICATE KEY
        UPDATE sge_event_id = LAST_INSERT_ID(sge_event_id)';

    return ( $sql, @values );
}

sub get_transform_query {
    my ($self) = @_;

    my @columns = map {qq[`$_`]} keys %map;
    my $columns     = join( ',', @columns );
    my $select_expr = join( ',', values %map );

    my $sql = qq{
        INSERT INTO event ( $columns )
        SELECT $select_expr
        FROM sge_event
        WHERE start_time != 0
        GROUP BY job_number, task_number
    };

    return $sql;
}

sub _set_resource_lists {
    my ( $self, $event, $category ) = @_;

    return if $category eq '';

    # Split on flags, but don't remove the flags
    my @parts = split /\s+?(?=-\w+)/, $category;

    foreach my $part (@parts) {
        my ( $flag, $value ) = split /\s+/, $part, 2;

        my $resources;

        if ( $flag eq '-l' ) {
            $resources = $self->_parse_resource_list_options($value);
        }
        elsif ( $flag eq '-pe' ) {
            $resources = $self->_parse_parallel_environment_options($value);
        }

        next unless ref $resources;

        # Merge resources into the event
        @$event{ keys %$resources } = values %$resources;
    }
}

sub _parse_resource_list_options {
    my ( $self, $options ) = @_;

    my %resources;

    my @options = split /,/, $options;

    foreach my $option (@options) {
        my ( $key, $value ) = split /=/, $option, 2;

        if ( !exists $resource_attributes{$key} ) {
            warn "Unknown resource attribute: '$key'";
            next;
        }

        if ( defined( $attribute_formats{$key} ) ) {
            my $parser = '_parse_' . $attribute_formats{$key};
            $value = $self->$parser($value);
        }

        $resources{"resource_list_$key"} = $value;
    }

    return \%resources;
}

sub _parse_parallel_environment_options {
    my ( $self, $options ) = @_;

    my ( $env, $slots ) = split /\s+/, $options;

    return { resource_list_slots => $slots };
}

sub _parse_memory {
    my ( $self, $memory ) = @_;

    if ( $memory =~ /^(\d*\.?\d+)(\D+)?$/ ) {
        my ( $value, $unit ) = ( $1, $2 );

        # SGE uses bytes by default
        $unit ||= 'b';
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

    $bytes = $value                      if $unit eq 'b';
    $bytes = $value * 1000               if $unit eq 'k';
    $bytes = $value * 1024               if $unit eq 'K';
    $bytes = $value * 1000 * 1000        if $unit eq 'm';
    $bytes = $value * 1024 * 1024        if $unit eq 'M';
    $bytes = $value * 1000 * 1000 * 1000 if $unit eq 'g';
    $bytes = $value * 1024 * 1024 * 1024 if $unit eq 'G';

    die "Unknown memory unit: $unit" unless defined $bytes;

    return int( $bytes / 1024 );
}

1;

__END__

=head1 NAME

Ubmod::Shredder::Sge - Parse Sun Grid Engine format accounting logs

=head1 VERSION

Version: $Id$

=head1 SYNOPSIS

    my $shredder = Ubmod::Shredder::Sge->new();
    my $event    = $shredder->shred($line);

=head1 DESCRIPTION

This module parses Sun Grid Engine accounting log files.  It defines a
class that implements a single public method to do so.

=head1 CONSTRUCTOR

=head2 new()

    my $shredder = Ubmod::Shredder::Sge->new();

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
