#!/usr/bin/env perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../lib";
use DBI;
use Getopt::Long;
use File::Spec;
use Config::Tiny;
use Ubmod::Shredder::Pbs;
use Ubmod::Aggregator;

my $Dbh;

sub main {
    my ( $file, $dir, $host, $shred, $update, $verbose, $help );

    Getopt::Long::Configure('bundling', 'ignorecase_always');

    my $result = GetOptions(
        'in|i=s'    => \$file,
        'dir|d=s'   => \$dir,
        'host|H=s'  => \$host,
        'shred|s'   => \$shred,
        'update|u'  => \$update,
        'verbose|v' => \$verbose,
        'help|h'    => \$help,
    );

    if ($help) {
        print usage();
        exit 0;
    }

    my $config = Config::Tiny->read("$FindBin::Bin/../config/settings.ini");
    db_connect( @{ $config->{database} }{qw{ dsn user password }} );

    if ($shred) {
        my $shredder = Ubmod::Shredder::Pbs->new();

        $shredder->set_host($host) if $host;

        log_msg("Shredding.");

        if ($dir) {
            process_directory( $shredder, $dir );
        }
        elsif ($file) {
            process_file( $shredder, $file );
        }

        log_msg("Done shredding!");
    }
    elsif ($update) {
        my $aggregator = Ubmod::Aggregator->new($Dbh);
        $aggregator->aggregate();
    }
    else {
        die usage();
    }
}

sub usage {
    return <<"EOT";
NAME
    $0 - shred accounting log files

SYNOPSIS
    $0 [-u] [-s] [-H host] [-i file|-d dir]

OPTIONS
    -i, --in
        input file

    -d, --dir
        location of accounting log directory

    -s, --shred
        shred accounting file(s)

    -u, --update
        update aggregate tables

    -H, --host hostname
        explicity set host from which the log file(s) originated from

    -v, --verbose
        verbose output

    -h, --help
        display this text and exit

EXAMPLES
    $0 -v -s -H your.host.org -d /var/spool/pbs/server_priv/accounting

    $0 -v -u
EOT
}

sub process_directory {
    my ( $shredder, $dir ) = @_;

    log_msg("Processing directory: $dir");

    -d $dir or die "Cannot access '$dir': No such directory\n";

    opendir my ($dh), $dir or die "Could not open die '$dir': $!";

    my $count = 0;

    while ( my $file = readdir($dh) ) {
        next if $file =~ /^\./;
        $count
            += process_file( $shredder, File::Spec->catfile( $dir, $file ) );
    }

    log_msg("Total shredded: $count");
}

sub process_file {
    my ( $shredder, $file ) = @_;

    log_msg("Processing file: $file");

    -f $file or die "Cannot access '$file': No such file\n";

    open my ($fh), '<', $file or die "Could not open file '$file': $!";

    $shredder->set_fh($fh);

    my $count = 0;
    while ( my $event = $shredder->shred() ) {
        my $event_id = insert_event($event);
        foreach my $host ( @{ $event->{hosts} } ) {
            insert_host_log( { %$host, event_id => $event_id } );
        }
        $count++;
    }

    log_msg("Shredded $count records.");

    return $count;
}

sub db_connect {
    my ( $dsn, $user, $pass ) = @_;
    $Dbh = DBI->connect( $dsn, $user, $pass );
}

sub insert_event {
    my ($event) = @_;
    my $sth = $Dbh->prepare(
        q{
            INSERT INTO event SET
                date_key = ?,
                job_id = ?,
                job_array_index = ?,
                host = LOWER(?),
                type = ?,
                user = LOWER(?),
                ugroup = LOWER(?),
                queue = LOWER(?),
                ctime = FROM_UNIXTIME(?),
                qtime = FROM_UNIXTIME(?),
                start = FROM_UNIXTIME(?),
                end = FROM_UNIXTIME(?),
                etime = FROM_UNIXTIME(?),
                exit_status = ?,
                session = ?,
                requestor = ?,
                jobname = ?,
                account = ?,
                exec_host = ?,
                resources_used_vmem = ?,
                resources_used_mem = ?,
                resources_used_walltime = ?,
                resources_used_nodes = ?,
                resources_used_cpus = ?,
                resources_used_cput = ?,
                resource_list_nodes = ?,
                resource_list_procs = ?,
                resource_list_neednodes = ?,
                resource_list_pcput = ?,
                resource_list_cput = ?,
                resource_list_walltime = ?,
                resource_list_ncpus = ?,
                resource_list_nodect = ?,
                resource_list_mem = ?,
                resource_list_pmem = ?
        }
    );
    $sth->execute(
        @$event{
            qw(
                date_key
                job_id
                job_array_index
                host
                type
                user
                group
                queue
                ctime
                qtime
                start
                end
                etime
                exit_status
                session
                requestor
                jobname
                account
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
                )
            }
    );

    return $Dbh->{mysql_insertid};
}

sub insert_host_log {
    my ($log) = @_;
    my $sth = $Dbh->prepare(
        q{
            INSERT INTO host_log SET
                event_id = ?,
                host = ?,
                cpu = ?
        }
    );
    $sth->execute( @$log{qw{ event_id host cpu }} );
}

sub get_event_max_date {
    my $sth = $Dbh->prepare(q{ SELECT MAX(date_key) FROM event });
    $sth->execute();
    return $sth->fetchrow_arrayref->[0];
}

sub log_msg {
    my ($msg) = @_;
    print $msg, "\n";
}

main(@ARGV) unless caller();
