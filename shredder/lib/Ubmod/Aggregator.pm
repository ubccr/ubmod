package Ubmod::Aggregator;
use strict;
use warnings;
use List::Util qw(max);
use DateTime;

sub new {
    my ( $class, %options ) = @_;

    my $self = {%options};

    if ( !defined $self->{end_date} ) {
        $self->{end_date} = DateTime->now()->subtract( days => 1 );
    }

    return bless $self, $class;
}

sub aggregate {
    my ($self) = @_;

    # Udpate dimensions
    $self->_update_clusters();
    $self->_update_queues();
    $self->_update_groups();
    $self->_update_users();
    $self->_update_dates();
    $self->_update_cpus();

    # Time intervals
    $self->_update_time_intervals();

    # Update roll-up dimensions
    $self->_update_roll_up_dimensions();

    # Update facts
    $self->_update_jobs();

    # Update aggregates
    $self->_update_job_aggregates();
}

sub _update_clusters {
    my ($self) = @_;

    $self->{logger}->info('Updating cluster dimension');

    my %clusters = %{ $self->_select_clusters() };
    my @names    = @{ $self->_select_distinct_from_event('cluster') };

    foreach my $name (@names) {
        if ( !defined $clusters{$name} ) {
            $self->{logger}->info("Adding new cluster: $name");
            my $id = $self->_insert_cluster( { name => $name } );
            $self->{logger}
                ->info("Successfully inserted new cluster with id: $id");
        }
        else {
            $self->{logger}->info("Cluster '$name' already exists.");
        }
    }
}

sub _update_queues {
    my ($self) = @_;

    $self->{logger}->info('Updating queue dimension');

    my %queues = %{ $self->_select_queues() };
    my @names  = @{ $self->_select_distinct_from_event('queue') };

    foreach my $name (@names) {
        if ( !defined $queues{$name} ) {
            $self->{logger}->info("Adding new queue: $name");
            my $id = $self->_insert_queue( { name => $name } );
            $self->{logger}
                ->info("Successfully inserted new queue with id: $id");
        }
        else {
            $self->{logger}->info("Queue '$name' already exists.");
        }
    }
}

sub _update_groups {
    my ($self) = @_;

    $self->{logger}->info('Updating group dimension');

    my %groups = %{ $self->_select_groups() };
    my @names  = @{ $self->_select_distinct_from_event('group') };

    foreach my $name (@names) {
        if ( !defined $groups{$name} ) {
            $self->{logger}->info("Adding new group: $name");
            my $id = $self->_insert_group( { name => $name } );
            $self->{logger}
                ->info("Successfully inserted new group with id: $id");
        }
        else {
            $self->{logger}->info("Group '$name' already exists.");
        }
    }
}

sub _update_users {
    my ($self) = @_;

    $self->{logger}->info('Updating user dimension');

    my %users = %{ $self->_select_users() };
    my @names = @{ $self->_select_distinct_from_event('user') };

    foreach my $name (@names) {
        if ( !defined $users{$name} ) {
            $self->{logger}->info("Adding new user: $name");
            my $id = $self->_insert_user( { name => $name } );
            $self->{logger}
                ->info("Successfully inserted new user with id: $id");
        }
        else {
            $self->{logger}->info("User '$name' already exists.");
        }
    }
}

sub _update_dates {
    my ($self) = @_;

    $self->{logger}->info('Updating date dimension');

    $self->_truncate('dim_date');

    my $min_date = $self->_get_min_date();
    my $max_date = $self->_get_max_date();

    $self->{logger}->info( 'Oldest day: ' . $min_date->ymd() );
    $self->{logger}->info( 'Most recent day: ' . $max_date->ymd() );

    return $self->_insert_dates( $min_date, $max_date );
}

sub _update_cpus {
    my ($self) = @_;

    $self->{logger}->info('Updating cpu dimension');

    $self->_truncate('dim_cpus');

    my $sql = q{SELECT MAX(`cpus`) FROM `event`};
    my ($max) = $self->{dbh}->selectrow_array($sql);

    $self->_insert_cpus($max);
}

sub _update_time_intervals {
    my ($self) = @_;

    $self->_truncate('time_interval');

    $self->_insert_time_interval(
        {   label  => 'Custom Date Range',
            custom => 1,
        }
    );

    my $end_date = $self->{end_date};

    my @labels = (
        [ 'Last 7 days',   7,   '{"last_7_days":1}' ],
        [ 'Last 30 days',  30,  '{"last_30_days":1}' ],
        [ 'Last 90 days',  90,  '{"last_90_days":1}' ],
        [ 'Last 365 days', 365, '{"last_365_days":1}' ],
    );

    foreach my $item (@labels) {
        my ( $label, $days, $clause ) = @$item;

        my $start_date = $end_date->clone()->subtract( days => $days - 1 );

        my $interval = {
            label        => $label,
            start        => $start_date->iso8601(),
            end          => $end_date->iso8601(),
            query_params => $clause,
        };
        $self->_insert_time_interval($interval);
    }

    foreach my $year ( @{ $self->_select_years() } ) {
        my $interval = {
            label        => $year,
            start        => "$year-01-01",
            end          => "$year-12-31",
            query_params => qq({"year":$year}),
        };

        $self->_insert_time_interval($interval);
    }
}

sub _get_cpu_min_max {
    return [
        [ 1,   1 ],
        [ 2,   2 ],
        [ 3,   4 ],
        [ 5,   8 ],
        [ 9,   16 ],
        [ 17,  32 ],
        [ 33,  64 ],
        [ 65,  128 ],
        [ 129, 256 ],
        [ 257, 512 ],
        [ 513, undef ],
    ];
}

sub _get_cpu_min_max_label {
    my ( $self, $min, $max ) = @_;

    if ( !defined $max ) {
        $min--;
        return ">$min";
    }
    elsif ( $min == $max ) {
        return $min;
    }
    else {
        return "$min-$max";
    }
}

sub _get_min_date {
    my ($self) = @_;

    if ( !exists $self->{min_date} ) {
        $self->_cache_min_and_max_dates();
    }

    return $self->{min_date};
}

sub _get_max_date {
    my ($self) = @_;

    if ( !exists $self->{max_date} ) {
        $self->_cache_min_and_max_dates();
    }

    return $self->{max_date};
}

sub _cache_min_and_max_dates {
    my ($self) = @_;

    my ( $min_date, $max_date ) = $self->_select_min_and_max_dates();

    if ( !$min_date || !$max_date ) {
        $self->{logger}->fatal('No dates found');
        return;
    }

    if ( $min_date =~ /^(\d{4})-(\d{1,2})-(\d{1,2})$/ ) {
        $self->{min_date}
            = DateTime->new( year => $1, month => $2, day => $3 );
    }
    else {
        $self->{logger}->fatal("Invalid date format: '$min_date'");
        return;
    }

    if ( $max_date =~ /^(\d{4})-(\d{1,2})-(\d{1,2})$/ ) {
        $self->{max_date}
            = DateTime->new( year => $1, month => $2, day => $3 );
    }
    else {
        $self->{logger}->fatal("Invalid date format: '$max_date'");
        return;
    }
}

# Database methods

sub _select_min_and_max_dates {
    my ($self) = @_;

    my $sql = q{
        SELECT
            DATE(MIN(`date_key`)),
            DATE(MAX(`date_key`))
        FROM `event`
    };

    return $self->{dbh}->selectrow_array($sql);
}

sub _select_clusters {
    my ($self) = @_;
    my $sql = q{SELECT * FROM `dim_cluster`};
    return $self->{dbh}->selectall_hashref( $sql, 'name' );
}

sub _select_queues {
    my ($self) = @_;
    my $sql = q{SELECT * FROM `dim_queue`};
    return $self->{dbh}->selectall_hashref( $sql, 'name' );
}

sub _select_groups {
    my ($self) = @_;
    my $sql = q{SELECT * FROM `dim_group`};
    return $self->{dbh}->selectall_hashref( $sql, 'name' );
}

sub _select_users {
    my ($self) = @_;
    my $sql = q{SELECT * FROM `dim_user`};
    return $self->{dbh}->selectall_hashref( $sql, 'name' );
}

sub _select_years {
    my ($self) = @_;

    my $sql = qq{
        SELECT DISTINCT year
        FROM dim_date
        ORDER BY year DESC
    };

    my $rows = $self->{dbh}->selectall_arrayref( $sql, { Slice => {} } );
    my @years = map { $_->{year} } @$rows;

    return \@years;
}

sub _select_distinct_from_event {
    my ( $self, $column ) = @_;

    my $sql = qq{
        SELECT DISTINCT `$column`
        FROM event
        WHERE `$column` IS NOT NULL
    };
    my $rows = $self->{dbh}->selectall_arrayref( $sql, { Slice => {} } );
    my @fields = map { $_->{$column} } @$rows;

    return \@fields;
}

sub _insert_cluster {
    my ( $self, $cluster ) = @_;

    my $sql = q{INSERT INTO `dim_cluster` SET `name` = ?};
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute( $cluster->{name} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_queue {
    my ( $self, $queue ) = @_;

    my $sql = q{INSERT INTO `dim_queue` SET `name` = ?};
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute( $queue->{name} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_group {
    my ( $self, $group ) = @_;

    my $sql = q{INSERT INTO `dim_group` SET `name` = ?};
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute( $group->{name} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_user {
    my ( $self, $user ) = @_;

    my $sql = q{INSERT INTO `dim_user` SET `name` = ?};
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute( $user->{name} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_time_interval {
    my ( $self, $interval ) = @_;

    my $sql = q{
        INSERT INTO `time_interval` SET
            `display_name` = ?,
            `start`        = ?,
            `end`          = ?,
            `custom`       = ?,
            `query_params` = ?
    };
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute( @$interval{qw( label start end custom query_params )} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_dates {
    my ( $self, $first_date, $last_date ) = @_;

    my $sql = q{
        INSERT INTO `dim_date` SET
            `date`          = ?,
            `week`          = ?,
            `month`         = ?,
            `quarter`       = ?,
            `year`          = ?,
            `last_7_days`   = ?,
            `last_30_days`  = ?,
            `last_90_days`  = ?,
            `last_365_days` = ?
    };
    my $sth = $self->{dbh}->prepare($sql);

    my $end     = $self->{end_date};
    my $current = $first_date->clone();

    while ( DateTime->compare( $current, $last_date ) <= 0 ) {
        my $delta   = $end->delta_days($current);
        my $days    = $delta->in_units('days');
        my $non_neg = DateTime->compare( $current, $end ) != 1;

        # XXX ISO week numbers start on Monday
        my $r = $sth->execute(
            $current->ymd,   $current->week_number,
            $current->month, $current->quarter,
            $current->year,  $non_neg && $days < 7,
            $non_neg && $days < 30, $non_neg && $days < 90,
            $non_neg && $days < 365,
        );
        if ( !$r ) {
            $self->{logger}->fatal( $sth->errstr() );
            return;
        }

        $current->add( days => 1 );
    }
}

sub _insert_cpus {
    my ( $self, $global_max ) = @_;

    my $sql = q{
        INSERT INTO `dim_cpus` SET
            `cpu_count`    = ?,
            `display_name` = ?,
            `view_order`   = ?
    };
    my $sth = $self->{dbh}->prepare($sql);

    my @min_max = @{ $self->_get_cpu_min_max() };

    # Make sure all cpu intervals are included
    $global_max = max( $global_max, $min_max[-1][0] );

    foreach my $i ( 0 .. $#min_max ) {
        my ( $min, $max ) = @{ $min_max[$i] };

        my $label = $self->_get_cpu_min_max_label( $min, $max );

        # The last interval needs a max
        $max ||= $global_max;

        foreach my $cpu ( $min .. $max ) {
            my $r = $sth->execute( $cpu, $label, $i );
            if ( !$r ) {
                $self->{logger}->fatal( $sth->errstr() );
                return;
            }
        }
    }
}

sub _update_jobs {
    my ($self) = @_;
    $self->{logger}->info('Updating job facts');
    $self->{dbh}->do(q{CALL UpdateJobFacts()});
}

sub _update_roll_up_dimensions {
    my ($self) = @_;
    $self->{logger}->info('Updating roll-up dimensions');
    $self->{dbh}->do(q{CALL UpdateRollUpDimensions()});
}

sub _update_job_aggregates {
    my ($self) = @_;
    $self->{logger}->info('Updating job aggregates');
    $self->{dbh}->do(q{CALL UpdateJobAggregates()});
}

sub _truncate {
    my ( $self, $table ) = @_;
    $self->{dbh}->do(qq{TRUNCATE `$table`});
}

1;

__END__

=head1 NAME

Ubmod::Aggregator - Populate database with aggregate accounting log data

=head1 VERSION

Version: $Id$

=head1 SYNOPSIS

    my $aggregator = Ubmod::Aggregator->new(
        dbh      => $dbh,
        logger   => $logger,
        end_date => $end_date,
    );
    $aggregator->aggregate();

=head1 DESCRIPTION

This module uses data in the C<event> table to produce aggregate data,
which can then be viewed using the UBMoD portal.

=head1 CONSTRUCTOR

=head2 new( dbh => $dbh, logger => $logger );

    my $aggregator = Ubmod::Aggregator->new(
        dbh      => $dbh,
        logger   => $logger,
        end_date => $end_date,
    );

Both the C<$dbh> and C<$logger> parameters are requried.  C<$dbh> should
be a DBI handle to a MySQL database prepared with the UBMoD schema.
C<$logger> should be an instance of C<Ubmod::Logger>.  The C<$end_date>
option is optional.  If supplied, it must be a C<DateTime> instance
indicating the end date of the time intervals used during aggregation.
By default yesterday is used as the C<$end_date>.

=head1 METHODS

=head2 aggregate()

Perform the aggregation process on data in the database.

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
