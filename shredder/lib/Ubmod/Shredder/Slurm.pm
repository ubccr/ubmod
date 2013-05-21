package Ubmod::Shredder::Slurm;
use strict;
use warnings;

use base qw(Ubmod::Shredder);

# Fields specified with "sacct --format".
# jobname must be last since it may contain a "|".
my @field_names = qw(
    jobid
    cluster
    partition
    account
    group
    user
    submit
    eligible
    start
    end
    exitcode
    nnodes
    ncpus
    nodelist
    jobname
);

my $field_count = scalar @field_names;

# Mapping from generic event table to Slurm specific event table.
my %map = (
    date_key        => 'DATE(`end`)',
    job_id          => '`jobid`',
    job_name        => '`jobname`',
    cluster         => '`cluster`',
    queue           => '`partition`',
    user            => '`user`',
    group           => '`group`',
    account         => '`account`',
    start_time      => '`start`',
    end_time        => '`end`',
    submission_time => '`submit`',
    wallt => '(UNIX_TIMESTAMP(`end`) - UNIX_TIMESTAMP(`start`)) * `ncpus`',
    wait  => 'UNIX_TIMESTAMP(`start`) - UNIX_TIMESTAMP(`submit`)',
    exect => 'UNIX_TIMESTAMP(`end`) - UNIX_TIMESTAMP(`start`)',
    nodes => '`nnodes`',
    cpus  => '`ncpus`',
);

# These are all the states for jobs that are no longer running.
my @states = qw(
    CANCELLED
    COMPLETED
    FAILED
    NODE_FAIL
    PREEMPTED
    TIMEOUT
);

sub shred_line {
    my ( $self, $line ) = @_;

    my @fields = split /\|/, $line, $field_count;

    if ( scalar @fields != $field_count ) {
        die "Malformed Slurm sacct line: $line";
    }

    my %event
        = map { $field_names[$_] => $fields[$_] } 0 .. scalar @fields - 1;

    # Skip job steps.
    return {} if $event{jobid} =~ /\./;

    # Skip jobs that haven't ended.
    return {} if $event{end} eq 'Unknown';

    $event{cluster} = $self->host() if $self->has_host();

    return \%event;
}

sub get_transform_map { return \%map; }

sub get_field_names { return \@field_names; }

sub get_states { return \@states; }

1;

__END__

=head1 NAME

Ubmod::Shredder::Slurm - Parse Slurm sacct output

=head1 VERSION

Version: $Id$

=head1 DESCRIPTION

This module parses Slurm sacct output.

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

