package Ubmod::Shredder::Pbs;
use strict;
use warnings;

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

my %params = (
    user                    => '',
    group                   => '',
    queue                   => '',
    ctime                   => '',
    qtime                   => '',
    start                   => '',
    end                     => '',
    etime                   => '',
    exit_status             => '',
    session                 => '',
    requestor               => '',
    jobname                 => '',
    account                 => '',
    exec_host               => '',
    resources_used_vmem     => 'memory',
    resources_used_mem      => 'memory',
    resources_used_walltime => 'time',
    resources_used_cput     => 'time',
    resource_list_pcput     => 'time',
    resource_list_cput      => 'time',
    resource_list_walltime  => 'time',
    resource_list_nodes     => '',
    resource_list_procs     => '',
    resource_list_neednodes => '',
    resource_list_ncpus     => '',
    resource_list_nodect    => '',
    resource_list_mem       => 'memory',
    resource_list_pmem      => 'memory',
);

sub new {
    my ($class) = @_;
    my $self = {};
    return bless $self, $class;
}

sub set_fh {
    my ( $self, $fh ) = @_;
    $self->{fh} = $fh;
}

sub set_host {
    my ( $self, $host ) = @_;
    $self->{host} = $host;
}

sub shred {
    my ($self) = @_;

    my $line = readline( $self->{fh} );

    return unless defined $line;

    if ( $line !~ $pattern ) {
        die "Malformed PBS acct line: $line\n";
    }

    my ( $date, $type, $job_id, $params ) = ( $1, $2, $3, $4 );

    $date =~ s#^(\d{2})/(\d{2})/(\d{4})#$3-$1-$2#;

    my $event = {
        date_key => $date,
        type     => $type,
        host     => $self->{host},
    };

    $self->set_job_id_and_host( $event, $job_id );

    my @parts = split /\s+/, $params;

    foreach my $part (@parts) {
        my ( $key, $value ) = split /=/, $part, 2;

        $key =~ s/\./_/g;
        $key = lc $key;

        if ( $key eq 'exec_host' ) {
            $self->set_exec_host( $event, $value );
        }

        if ( defined( my $type = $params{$key} ) ) {
            if ( $type ne '' ) {
                my $parser = "parse_$type";
                $value = $self->$parser($value);
            }
            $event->{$key} = $value;
        }
    }

    return $event;
}

sub parse_time {
    my ( $self, $time ) = @_;

    my ( $h, $m, $s ) = split /:/, $time;
    return $h * 60 * 60 + $m * 60 + $s;
}

sub parse_memory {
    my ( $self, $memory ) = @_;

    my $unit = 'kb';

    if ( $memory =~ /^\d+(.*)/ ) {
        $unit = $1;
    }

    $memory =~ s/\D+//g;

    return $self->scale_memory( $unit, $memory );
}

sub scale_memory {
    my ( $self, $unit, $value ) = @_;

    # XXX because our default unit is KB just return 1
    return 1 if $unit eq 'b';

    return $value               if $unit eq 'kb';
    return $value * 1024        if $unit eq 'mb';
    return $value * 1024 * 1024 if $unit eq 'gb';
}

sub set_job_id_and_host {
    my ( $self, $event, $job_id ) = @_;

    my ( $job, $host ) = split /\./, $job_id, 2;

    my ( $id, $index ) = split /-/, $job;

    $event->{job_id}          = $id;
    $event->{job_array_index} = $index;

    $event->{host} = $host if !defined $event->{host};
}

sub set_exec_host {
    my ( $self, $event, $hosts_str ) = @_;

    my $cpus  = 0;
    my $nodes = 0;

    my $hosts = $self->parse_hosts($hosts_str);

    my %map;
    foreach my $host (@$hosts) {
        $map{ $host->{host} } += 1;
    }

    while ( my ( $host, $total ) = each(%map) ) {
        $nodes++;
        $cpus += $total;
    }

    $event->{resources_used_nodes} = $nodes;
    $event->{resources_used_cpus}  = $cpus;
    $event->{hosts}                = $hosts;
}

sub parse_hosts {
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
