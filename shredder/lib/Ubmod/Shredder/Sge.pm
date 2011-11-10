package Ubmod::Shredder::Sge;
use strict;
use warnings;

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

# These resource attributes may require formatting
my %resource_formats = (
    s_data  => 'memory',
    h_data  => 'memory',
    s_stack => 'memory',
    h_stack => 'memory',
    s_core  => 'memory',
    h_core  => 'memory',
    s_rss   => 'memory',
    h_rss   => 'memory',
    s_vmem  => 'memory',
    h_vmem  => 'memory',
    s_fsize => 'memory',
    h_fsize => 'memory',
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
    start_time      => 'FROM_UNIXTIME(MIN(start_time))',
    end_time        => 'FROM_UNIXTIME(MAX(end_time))',
    submission_time => 'FROM_UNIXTIME(MIN(submission_time))',
    wallt           => 'AVG(ru_wallclock)',
    cput            => 'SUM(cpu)',
    mem             => 'SUM(mem * 1024 * 1024 / cpu)',
    vmem            => 'SUM(maxvmem) / 1024',
    nodes           => 'COUNT(DISTINCT hostname)',
    cpus            => 'slots',
);

sub new {
    my ($class) = @_;
    my $self = {};
    return bless $self, $class;
}

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

    $event{cluster} = $self->get_host();

    return \%event;
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

sub set_host {
    my ( $self, $host ) = @_;
    $self->{host} = $host;
}

sub get_host {
    my ($self) = @_;
    return $self->{host};
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

        if ( defined( $resource_formats{$key} ) ) {
            my $parser = '_parse_' . $resource_formats{$key};
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

    if ( $memory =~ /^\d+$/ ) {
        return $self->_scale_memory( $memory, 'b' );
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

    my $bytes;

    $bytes = $value                      if $unit eq 'b';
    $bytes = $value * 1000               if $unit eq 'k';
    $bytes = $value * 1024               if $unit eq 'K';
    $bytes = $value * 1000 * 1000        if $unit eq 'm';
    $bytes = $value * 1024 * 1024        if $unit eq 'M';
    $bytes = $value * 1000 * 1000 * 1000 if $unit eq 'g';
    $bytes = $value * 1024 * 1024 * 1024 if $unit eq 'G';

    die "Unknown memory unit: $unit" unless defined $bytes;

    return $bytes / 1024;
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
