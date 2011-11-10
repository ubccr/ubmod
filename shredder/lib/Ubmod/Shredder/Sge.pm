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

# These entries need to be parsed and formatted
my %formats = ( ru_maxrss => 'bytes' );

# Mapping from SGE entry names to database field names
my %map = (
    job_number      => 'job_id',
    task_number     => 'job_array_index',
    qname           => 'queue',
    owner           => 'user',
    group           => 'group',
    submission_time => 'ctime',
    start_time      => 'start',
    end_time        => 'end',
    exit_status     => 'exit_status',
    job_name        => 'jobname',
    account         => 'account',
    hostname        => 'exec_host',
    maxvmem         => 'resources_used_vmem',
    ru_maxrss       => 'resources_used_mem',
    ru_wallclock    => 'resources_used_walltime',
    slots           => 'resources_used_cpus',
    cpu             => 'resources_used_cput',
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
    my %entries
        = map { $entry_names[$_] => $entries[$_] } 0 .. scalar @entries - 1;

    my %event = (
        date_key             => $self->_from_unixtime( $entries{end_time} ),
        type                 => 'E',
        resources_used_nodes => 1,
    );

    while ( my ( $key, $value ) = each(%entries) ) {

        if ( defined( $formats{$key} ) ) {
            my $parser = '_parse_' . $formats{$key};
            $value = $self->$parser($value);
        }

        $event{ $map{$key} } = $value if defined $map{$key};
    }

    return \%event;
}

sub _from_unixtime {
    my ( $self, $time ) = @_;

    my ( $sec, $min, $hour, $mday, $mon, $year ) = localtime($time);

    return sprintf(
        '%04d-%02d-%02d %02d:%02d:%02d',
        $year + 1900,
        $mon + 1, $mday, $hour, $min, $sec
    );
}

# Scale from bytes to kilobytes
sub _parse_bytes {
    my ( $self, $bytes ) = @_;
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
