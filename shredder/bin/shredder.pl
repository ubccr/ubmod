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
use Ubmod::Logger;

my $Dbh;
my $Logger;

sub main {
    my ( $stdio, $file, $dir, $host, $shred, $update, $verbose, $help );

    Getopt::Long::Configure('no_ignore_case');

    my $result = GetOptions(
        ''          => \$stdio,
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

    $Logger = Ubmod::Logger->new($verbose);

    my $config = Config::Tiny->read("$FindBin::Bin/../config/settings.ini");
    db_connect( @{ $config->{database} }{qw{ dsn user password }} );

    if ($shred) {
        my $shredder = Ubmod::Shredder::Pbs->new( logger => $Logger );

        $shredder->set_host($host) if $host;

        $Logger->info("Shredding.");

        if ($dir) {
            process_directory( $shredder, $dir );
        }
        elsif ($file) {
            process_file( $shredder, $file );
        }
        elsif ($stdio) {
            $Logger->info("Processing standard input");
            process_fh( $shredder, *STDIN );
        }
        else {
            print usage();
            exit 1;
        }

        $Logger->info("Done shredding!");
    }

    if ($update) {
        my $aggregator
            = Ubmod::Aggregator->new( dbh => $Dbh, logger => $Logger );
        $aggregator->aggregate();
    }

    if ( !$update && !$shred ) {
        print usage();
        exit 1;
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

    $Logger->info("Processing directory: $dir");

    if ( !-d $dir ) {
        $Logger->fatal("Cannot access '$dir': No such directory");
        exit 1;
    }

    my $dh;
    if ( !opendir $dh, $dir ) {
        $Logger->fatal("Could not open dir '$dir': $!");
        exit 1;
    }

    my $count = 0;

    while ( my $file = readdir($dh) ) {

        # Skip hidden files
        next if $file =~ /^\./;

        my $file_path = File::Spec->catfile( $dir, $file );
        if ( !-f $file_path ) {
            $Logger->warn("Skipping '$file'.");
            next;
        }
        if ( !-r $file_path ) {
            $Logger->warn("Skipping unreadable file '$file'.");
            next;
        }

        $count += process_file( $shredder, $file_path );
    }

    $Logger->info("Total shredded: $count");
}

sub process_file {
    my ( $shredder, $file ) = @_;

    $Logger->info("Processing file: $file");

    if ( !-f $file ) {
        $Logger->fatal("Cannot access '$file': No such file");
        exit 1;
    }

    my $fh;
    if ( !open $fh, '<', $file ) {
        $Logger->fatal("Could not open file '$file': $!");
        exit 1;
    }

    return process_fh( $shredder, $fh );
}

sub process_fh {
    my ( $shredder, $fh ) = @_;

    $shredder->set_fh($fh);

    my $count = 0;
    while ( my $event = $shredder->shred() ) {
        next unless %$event;
        my $event_id = insert_event($event);
        foreach my $host ( @{ $event->{hosts} } ) {
            insert_host_log( { %$host, event_id => $event_id } );
        }
        $count++;
    }

    $Logger->info("Shredded $count records.");

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

main(@ARGV) unless caller();

__END__

=head1 NAME

shredder.pl - UBMoD shredder script

=head1 SYNOPSIS

  ./shredder.pl -s -H your.host.org -d /var/spool/pbs/server_priv/accounting

  ./shredder.pl -u

  ./shredder.pl -h

=head1 DESCRIPTION

This script can be used to parse and aggregate accounting data for use
with the UBMoD portal.

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
