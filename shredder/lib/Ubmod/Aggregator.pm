package Ubmod::Aggregator;
use strict;
use warnings;
use List::Util qw(max);
use DateTime;
use JSON;

sub new {
    my ( $class, %options ) = @_;

    my $self = {%options};

    if ( !defined $self->{end_date} ) {
        $self->{end_date}
            = DateTime->now( time_zone => 'local' )->subtract( days => 1 );
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
    $self->_update_tags();
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

    # Update other fields
    $self->_update_users_current_group();
}

sub _update_clusters {
    my ($self) = @_;

    $self->{logger}->info('Updating cluster dimension');

    my %clusters = %{ $self->_select_clusters() };
    my @names    = @{ $self->_select_distinct_from_event('cluster') };

    for my $name (@names) {
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

    for my $name (@names) {
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

    for my $name (@names) {
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

    for my $name (@names) {
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

    return \@names;
}

sub _update_users_current_group {
    my ( $self, $users ) = @_;

    $self->{logger}->info('Updating user current group');

    my %users = %{ $self->_select_users_current_group() };

    while ( my ( $id, $user ) = each %users ) {
        my ( $name, $group ) = @$user{qw( name group )};
        $self->{logger}
            ->info("Setting user '$name' current group to '$group'.");
        $self->_update_user_current_group( $id, $group );
    }
}

sub _update_tags {
    my ($self) = @_;

    $self->{logger}->info('Updating tags dimension');

    $self->_truncate('br_tags_to_tag');
    $self->_truncate('dim_tags');

    my @tags = @{ $self->_select_distinct_from_event('tags') };

    for my $json (@tags) {
        $self->_insert_tags($json);
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

    for my $item (@labels) {
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

    for my $year ( @{ $self->_select_years() } ) {
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
        die;
    }

    if ( $min_date =~ /^(\d{4})-(\d{1,2})-(\d{1,2})$/ ) {
        $self->{min_date}
            = DateTime->new( year => $1, month => $2, day => $3 );
    }
    else {
        $self->{logger}->fatal("Invalid date format: '$min_date'");
        die;
    }

    if ( $max_date =~ /^(\d{4})-(\d{1,2})-(\d{1,2})$/ ) {
        $self->{max_date}
            = DateTime->new( year => $1, month => $2, day => $3 );
    }
    else {
        $self->{logger}->fatal("Invalid date format: '$max_date'");
        die;
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

sub _select_tag_id {
    my ( $self, $tag ) = @_;
    my $sql = q{SELECT `dim_tag_id` FROM `dim_tag` WHERE `name` = ?};
    my $row = $self->{dbh}->selectrow_hashref( $sql, undef, $tag );
    return unless $row;
    return $row->{dim_tag_id};
}

sub _select_years {
    my ($self) = @_;

    my $sql = q{
        SELECT DISTINCT `year`
        FROM `dim_date`
        ORDER BY `year` DESC
    };

    my $rows = $self->{dbh}->selectall_arrayref( $sql, { Slice => {} } );
    my @years = map { $_->{year} } @$rows;

    return \@years;
}

sub _select_distinct_from_event {
    my ( $self, $column ) = @_;

    my $sql = qq{
        SELECT DISTINCT `$column`
        FROM `event`
        WHERE `$column` IS NOT NULL
    };
    my $rows = $self->{dbh}->selectall_arrayref( $sql, { Slice => {} } );
    my @fields = map { $_->{$column} } @$rows;

    return \@fields;
}

sub _select_users_current_group {
    my ( $self ) = @_;

    # The combination of SUBSTRING_INDEX and GROUP_CONCAT used below
    # selects the last group for each user when the groups are order
    # by date.  It is assumed that group names don't contain the comma
    # (",") character.
    my $sql = q{
        SELECT
            dim_user.dim_user_id,
            dim_user.name,
            SUBSTRING_INDEX(
                GROUP_CONCAT(dim_group.name ORDER BY dim_date.date DESC),
                ',',
                1
            ) AS `group`
        FROM fact_job
        JOIN dim_user  USING (dim_user_id)
        JOIN dim_group USING (dim_group_id)
        JOIN dim_date  USING (dim_date_id)
        GROUP BY dim_user.dim_user_id
    };

    return $self->{dbh}->selectall_hashref( $sql, 'dim_user_id' );
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

sub _insert_tags {
    my ( $self, $json ) = @_;

    my $sql = q{INSERT INTO `dim_tags` SET `tags` = ?};
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute($json);

    my $tags_id = $self->{dbh}->{mysql_insertid};

    my @tags;
    eval {
        @tags = @{ decode_json($json) };
        1;
    } or do {
        $self->{logger}->fatal("Error decoding tags: $@");
        return $tags_id;
    };

    $sql = q{
        INSERT INTO `br_tags_to_tag` SET
            `dim_tags_id` = ?,
            `dim_tag_id`  = ?
    };
    $sth = $self->{dbh}->prepare($sql);

    for my $tag (@tags) {
        my $tag_id = $self->_select_tag_id($tag);

        $tag_id = $self->_insert_tag($tag) unless $tag_id;

        $sth->execute( $tags_id, $tag_id );
    }

    return $tags_id;
}

sub _insert_tag {
    my ( $self, $tag ) = @_;

    my ( $key, $value );

    if ( $tag =~ /=/ ) {
        ( $key, $value ) = split /=/, $tag, 2;
    }

    my $sql = q{
        INSERT INTO `dim_tag` SET
            `name`  = ?,
            `key`   = ?,
            `value` = ?
    };
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute( $tag, $key, $value );

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

    for my $i ( 0 .. $#min_max ) {
        my ( $min, $max ) = @{ $min_max[$i] };

        my $label = $self->_get_cpu_min_max_label( $min, $max );

        # The last interval needs a max
        $max ||= $global_max;

        for my $cpu ( $min .. $max ) {
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

sub _update_user_current_group {
    my ( $self, $id, $group ) = @_;
    my $sql = q{UPDATE dim_user SET current_group = ? WHERE dim_user_id = ?};
    my $sth = $self->{dbh}->prepare($sql);
    $sth->execute( $group, $id );
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
