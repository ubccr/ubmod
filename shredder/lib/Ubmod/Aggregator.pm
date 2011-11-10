package Ubmod::Aggregator;
use strict;
use warnings;
use DateTime;

sub new {
    my ( $class, %options ) = @_;

    my $self = \%options;

    if ( !defined $self->{end_date} ) {
        $self->{end_date} = DateTime->now()->subtract( days => 1 );
    }
    $self->{end_date}->set( hour => 23, minute => 59, second => 59 );

    return bless $self, $class;
}

sub aggregate {
    my ($self) = @_;

    my $clusters  = $self->_update_clusters();
    my $queues    = $self->_update_queues($clusters);
    my $groups    = $self->_update_groups($clusters);
    my $users     = $self->_update_users( $clusters, $groups, $queues );
    my $intervals = $self->_update_intervals();

    $self->_truncate_activity();

    $self->_update_cluster_activity( $clusters, $intervals );
    $self->_update_queue_activity( $queues, $clusters, $intervals );
    $self->_update_group_activity( $groups, $clusters, $intervals );
    $self->_update_user_activity( $users, $clusters, $intervals );

    $self->_update_cpu_consumption( $clusters, $intervals );
    $self->_update_actual_wait_time( $clusters, $intervals );
}

sub _update_clusters {
    my ($self) = @_;

    my %clusters = map { $_->{cluster} => $_ } @{ $self->_select_clusters() };

    foreach my $cluster ( @{ $self->_select_event_clusters() } ) {
        my $name = $cluster->{cluster};
        if ( !defined $clusters{$name} ) {
            $self->{logger}->info("Adding new cluster: $name");
            my $id = $self->_insert_cluster( { cluster => $name } );
            $self->{logger}
                ->info("Successfully inserted new cluster with id: $id");
            $cluster->{cluster_id} = $id;
            $clusters{$name} = $cluster;
        }
        else {
            $self->{logger}->info("Cluster '$name' already exists.");
        }
    }

    return \%clusters;
}

sub _update_queues {
    my ( $self, $clusters ) = @_;

    my %queues = map { $_->{queue} => $_ } @{ $self->_select_queues() };

    foreach my $queue ( @{ $self->_select_event_queues() } ) {
        my $name = $queue->{queue};
        if ( !defined $queues{$name} ) {
            $self->{logger}->info("Adding new queue: $name");
            my $id = $self->_insert_queue( { queue => $name } );
            $self->{logger}
                ->info("Successfully inserted new queue with id: $id");
            $queue->{queue_id} = $id;
            $queues{$name} = $queue;
        }
        else {
            $self->{logger}->info("Queue '$name' already exists.");
            $queue->{queue_id} = $queues{$name}->{queue_id};
        }

        if ( my $cluster = $clusters->{ $queue->{cluster} } ) {
            my $params = {
                queue_id   => $queue->{queue_id},
                cluster_id => $cluster->{cluster_id},
            };
            $self->_delete_queue_cluster($params);
            $self->_insert_queue_cluster($params);
        }
    }

    return \%queues;
}

sub _update_groups {
    my ( $self, $clusters ) = @_;

    my %groups = map { $_->{group_name} => $_ } @{ $self->_select_groups() };

    foreach my $group ( @{ $self->_select_event_groups() } ) {
        my $name = $group->{group};
        if ( !defined $groups{$name} ) {
            $self->{logger}->info("Adding new group: $name");
            my $id = $self->_insert_group( { group_name => $name } );
            $self->{logger}
                ->info("Successfully inserted new group with id: $id");
            $group->{group_id} = $id;
            $groups{$name} = $group;
        }
        else {
            $self->{logger}->info("Group '$name' already exists.");
            $group->{group_id} = $groups{$name}->{group_id};
        }

        if ( my $cluster = $clusters->{ $group->{cluster} } ) {
            my $params = {
                group_id   => $group->{group_id},
                cluster_id => $cluster->{cluster_id},
            };
            $self->_delete_group_cluster($params);
            $self->_insert_group_cluster($params);
        }
    }

    return \%groups;
}

sub _update_users {
    my ( $self, $clusters, $groups, $queues ) = @_;

    my %users = map { $_->{user} => $_ } @{ $self->_select_users() };

    foreach my $user ( @{ $self->_select_event_users() } ) {
        my $name = $user->{user};
        if ( !defined $users{$name} ) {
            $self->{logger}->info("Adding new user: $name");
            my $id = $self->_insert_user( { user => $name } );
            $self->{logger}
                ->info("Successfully inserted new user with id: $id");
            $user->{user_id} = $id;
            $users{$name} = $user;
        }
        else {
            $self->{logger}->info("User '$name' already exists.");
            $user->{user_id} = $users{$name}->{user_id};
        }

        my $params = { user_id => $user->{user_id} };

        if ( my $cluster = $clusters->{ $user->{cluster} } ) {
            $params->{cluster_id} = $cluster->{cluster_id};
            $self->_delete_user_cluster($params);
            $self->_insert_user_cluster($params);
        }

        if ( my $group = $groups->{ $user->{group} } ) {
            $params->{group_id} = $group->{group_id};
            $self->_delete_user_group($params);
            $self->_insert_user_group($params);
        }

        if ( my $queue = $queues->{ $user->{queue} } ) {
            $params->{queue_id} = $queue->{queue_id};
            $self->_delete_user_queue($params);
            $self->_insert_user_queue($params);
        }
    }

    return \%users;
}

sub _update_intervals {
    my ($self) = @_;

    my $end_date = $self->{end_date};

    $self->_truncate_interval();

    my @labels = (
        [ 'Last 7 days',   7 ],
        [ 'Last 30 days',  30 ],
        [ 'Last 90 days',  90 ],
        [ 'Last 365 days', 365 ],
    );

    my %intervals;
    foreach my $item (@labels) {
        my ( $label, $days ) = @$item;

        my $start_date = $end_date->clone()->subtract( days => $days );
        $start_date->set( hour => 0, minute => 0, second => 0 );

        my $interval = {
            label => $label,
            start => $start_date->iso8601(),
            end   => $end_date->iso8601(),
        };
        my $id = $self->_insert_interval($interval);
        $interval->{interval_id} = $id;
        $intervals{$label} = $interval;
    }

    return \%intervals;
}

sub _update_cluster_activity {
    my ( $self, $clusters, $intervals ) = @_;

    $self->_truncate_cluster_activity();

    foreach my $interval ( values %$intervals ) {
        my $activities = $self->_select_cluster_activity($interval);

        foreach my $activity (@$activities) {
            my $id      = $self->_insert_activity($activity);
            my $cluster = $clusters->{ $activity->{cluster} };
            if ( !defined $cluster ) {
                $self->{logger}->info("Skipping cluster activity.");
                next;
            }

            $self->_insert_cluster_activity(
                {   user_count  => $activity->{user_count},
                    group_count => $activity->{group_count},
                    interval_id => $interval->{interval_id},
                    cluster_id  => $cluster->{cluster_id},
                    activity_id => $id,
                }
            );
        }
    }
}

sub _update_queue_activity {
    my ( $self, $queues, $clusters, $intervals ) = @_;

    $self->_truncate_queue_activity();

    foreach my $interval ( values %$intervals ) {
        my $activities = $self->_select_queue_activity($interval);

        foreach my $activity (@$activities) {
            my $id      = $self->_insert_activity($activity);
            my $cluster = $clusters->{ $activity->{cluster} };
            my $queue   = $queues->{ $activity->{queue} };
            if ( !defined $cluster || !defined $queue ) {
                $self->{logger}->info("Skipping queue activity.");
                next;
            }

            $self->_insert_queue_activity(
                {   user_count  => $activity->{user_count},
                    group_count => $activity->{group_count},
                    interval_id => $interval->{interval_id},
                    cluster_id  => $cluster->{cluster_id},
                    queue_id    => $queue->{queue_id},
                    activity_id => $id,
                }
            );
        }
    }
}

sub _update_group_activity {
    my ( $self, $groups, $clusters, $intervals ) = @_;

    $self->_truncate_group_activity();

    foreach my $interval ( values %$intervals ) {
        my $activities = $self->_select_group_activity($interval);

        foreach my $activity (@$activities) {
            my $id      = $self->_insert_activity($activity);
            my $cluster = $clusters->{ $activity->{cluster} };
            my $group   = $groups->{ $activity->{group} };
            if ( !defined $cluster || !defined $group ) {
                $self->{logger}->info("Skipping group activity.");
                next;
            }

            $self->_insert_group_activity(
                {   user_count  => $activity->{user_count},
                    interval_id => $interval->{interval_id},
                    cluster_id  => $cluster->{cluster_id},
                    group_id    => $group->{group_id},
                    activity_id => $id,
                }
            );
        }
    }
}

sub _update_user_activity {
    my ( $self, $users, $clusters, $intervals ) = @_;

    $self->_truncate_user_activity();

    foreach my $interval ( values %$intervals ) {
        my $activities = $self->_select_user_activity($interval);

        foreach my $activity (@$activities) {
            my $id      = $self->_insert_activity($activity);
            my $cluster = $clusters->{ $activity->{cluster} };
            my $user    = $users->{ $activity->{user} };
            if ( !defined $cluster || !defined $user ) {
                $self->{logger}->info("Skipping user activity.");
                next;
            }

            $self->_insert_user_activity(
                {   interval_id => $interval->{interval_id},
                    cluster_id  => $cluster->{cluster_id},
                    user_id     => $user->{user_id},
                    activity_id => $id,
                }
            );
        }
    }
}

sub _update_cpu_consumption {
    my ( $self, $clusters, $intervals ) = @_;

    $self->_truncate_cpu_consumption();

    my $cpus = $self->_get_cpu_min_max();

    foreach my $cluster ( values %$clusters ) {
        foreach my $interval ( values %$intervals ) {
            my $counter = 0;
            foreach my $min_max (@$cpus) {
                my ( $min, $max ) = @$min_max;
                my $consumption = $self->_select_cpu_consumption(
                    {   cluster => $cluster->{cluster},
                        start   => $interval->{start},
                        end     => $interval->{end},
                        min     => $min,
                        max     => $max,
                    }
                );

                my $label = $self->_get_cpu_min_max_label( $min, $max );

                if ( !defined $consumption->{cput} ) {
                    $self->{logger}->warn( "No cput found for cpus $label"
                            . " for time period $interval->{start}"
                            . " - $interval->{end}"
                            . " for cluster $cluster->{cluster}" );
                    $consumption->{cput} = 0;
                }

                $self->_insert_cpu_consumption(
                    {   interval_id => $interval->{interval_id},
                        cluster_id  => $cluster->{cluster_id},
                        label       => $label,
                        view_order  => $counter,
                        cput        => $consumption->{cput},
                    }
                );

                $counter++;
            }
        }
    }
}

sub _update_actual_wait_time {
    my ( $self, $clusters, $intervals ) = @_;

    $self->_truncate_actual_wait_time();

    my $cpus = $self->_get_cpu_min_max();

    foreach my $cluster ( values %$clusters ) {
        foreach my $interval ( values %$intervals ) {
            my $counter = 0;
            foreach my $min_max (@$cpus) {
                my ( $min, $max ) = @$min_max;
                my $wait_time = $self->_select_actual_wait_time(
                    {   cluster => $cluster->{cluster},
                        start   => $interval->{start},
                        end     => $interval->{end},
                        min     => $min,
                        max     => $max,
                    }
                );

                my $label = $self->_get_cpu_min_max_label( $min, $max );

                if ( !defined $wait_time->{avg_wait} ) {
                    $self->{logger}->warn( "No avg_wait found for cpus $label"
                            . " for time period $interval->{start}"
                            . " - $interval->{end}"
                            . " for cluster $cluster->{cluster}" );
                    $wait_time->{avg_wait} = 0;
                }

                $self->_insert_actual_wait_time(
                    {   interval_id => $interval->{interval_id},
                        cluster_id  => $cluster->{cluster_id},
                        label       => $label,
                        view_order  => $counter,
                        avg_wait    => $wait_time->{avg_wait},
                    }
                );

                $counter++;
            }
        }
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

# Database methods

sub _select_clusters {
    my ($self) = @_;

    my $sql = q{
        SELECT cluster_id, host AS cluster, display_name
        FROM cluster
    };
    return $self->{dbh}->selectall_arrayref( $sql, { Slice => {} } );
}

sub _select_queues {
    my ($self) = @_;

    return $self->{dbh}
        ->selectall_arrayref( q{ SELECT * FROM queue }, { Slice => {} } );
}

sub _select_groups {
    my ($self) = @_;

    return $self->{dbh}
        ->selectall_arrayref( q{ SELECT * FROM research_group },
        { Slice => {} } );
}

sub _select_users {
    my ($self) = @_;

    return $self->{dbh}
        ->selectall_arrayref( q{ SELECT * FROM user }, { Slice => {} } );
}

sub _select_event_clusters {
    my ($self) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{ SELECT DISTINCT cluster FROM event WHERE cluster IS NOT NULL },
        { Slice => {} } );
}

sub _select_event_queues {
    my ($self) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{
            SELECT cluster, queue
            FROM event
            WHERE cluster IS NOT NULL AND queue IS NOT NULL
            GROUP BY cluster, queue
        },
        { Slice => {} }
    );
}

sub _select_event_groups {
    my ($self) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{
            SELECT cluster, `group`
            FROM event
            WHERE cluster IS NOT NULL AND `group` IS NOT NULL
            GROUP BY cluster, `group`
        },
        { Slice => {} }
    );
}

sub _select_event_users {
    my ($self) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{
            SELECT cluster, user, queue, `group`
            FROM event
            WHERE cluster IS NOT NULL AND user IS NOT NULL
            GROUP BY cluster, user, queue, `group`
        },
        { Slice => {} }
    );
}

sub _select_cluster_activity {
    my ( $self, $interval ) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{
            SELECT
                cluster,
                queue,
                user,
                `group`,
                COUNT(DISTINCT(user)) AS user_count,
                COUNT(DISTINCT(`group`)) AS group_count,
                COUNT(*) as jobs,
                SUM(wallt) AS wallt,
                ROUND(AVG(wallt)) AS avg_wallt,
                MAX(wallt) AS max_wallt,
                SUM(cput) AS cput,
                ROUND(AVG(cput)) AS avg_cput,
                MAX(cput) AS max_cput,
                ROUND(AVG(mem)) AS avg_mem,
                MAX(mem) AS max_mem,
                ROUND(AVG(vmem)) AS avg_vmem,
                MAX(vmem) AS max_vmem,
                ROUND(AVG(wait)) AS avg_wait,
                ROUND(AVG(exect)) AS avg_exect,
                ROUND(AVG(nodes)) AS avg_nodes,
                MAX(nodes) AS max_nodes,
                ROUND(AVG(cpus)) AS avg_cpus,
                MAX(cpus) AS max_cpus
            FROM event e
            WHERE
                date_key BETWEEN ? AND ?
            GROUP BY cluster
        },
        { Slice => {} },
        @$interval{qw( start end )}
    );
}

sub _select_queue_activity {
    my ( $self, $interval ) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{
            SELECT
                cluster,
                queue,
                user,
                `group`,
                COUNT(DISTINCT(user)) AS user_count,
                COUNT(DISTINCT(`group`)) AS group_count,
                COUNT(*) as jobs,
                SUM(wallt) AS wallt,
                ROUND(AVG(wallt)) AS avg_wallt,
                MAX(wallt) AS max_wallt,
                SUM(cput) AS cput,
                ROUND(AVG(cput)) AS avg_cput,
                MAX(cput) AS max_cput,
                ROUND(AVG(mem)) AS avg_mem,
                MAX(mem) AS max_mem,
                ROUND(AVG(vmem)) AS avg_vmem,
                MAX(vmem) AS max_vmem,
                ROUND(AVG(wait)) AS avg_wait,
                ROUND(AVG(exect)) AS avg_exect,
                ROUND(AVG(nodes)) AS avg_nodes,
                MAX(nodes) AS max_nodes,
                ROUND(AVG(cpus)) AS avg_cpus,
                MAX(cpus) AS max_cpus
            FROM event e
            WHERE date_key BETWEEN ? AND ?
            AND cluster IS NOT NULL AND queue IS NOT NULL
            GROUP BY cluster, queue
        },
        { Slice => {} },
        @$interval{qw( start end )}
    );
}

sub _select_group_activity {
    my ( $self, $interval ) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{
            SELECT
                cluster,
                queue,
                user,
                `group`,
                COUNT(DISTINCT(user)) AS user_count,
                COUNT(DISTINCT(`group`)) AS group_count,
                COUNT(*) as jobs,
                SUM(wallt) AS wallt,
                ROUND(AVG(wallt)) AS avg_wallt,
                MAX(wallt) AS max_wallt,
                SUM(cput) AS cput,
                ROUND(AVG(cput)) AS avg_cput,
                MAX(cput) AS max_cput,
                ROUND(AVG(mem)) AS avg_mem,
                MAX(mem) AS max_mem,
                ROUND(AVG(vmem)) AS avg_vmem,
                MAX(vmem) AS max_vmem,
                ROUND(AVG(wait)) AS avg_wait,
                ROUND(AVG(exect)) AS avg_exect,
                ROUND(AVG(nodes)) AS avg_nodes,
                MAX(nodes) AS max_nodes,
                ROUND(AVG(cpus)) AS avg_cpus,
                MAX(cpus) AS max_cpus
            FROM event e
            WHERE date_key BETWEEN ? AND ?
            GROUP BY cluster, `group`
        },
        { Slice => {} },
        @$interval{qw( start end )}
    );
}

sub _select_user_activity {
    my ( $self, $interval ) = @_;

    return $self->{dbh}->selectall_arrayref(
        q{
            SELECT
                cluster,
                queue,
                user,
                `group`,
                COUNT(DISTINCT(user)) AS user_count,
                COUNT(DISTINCT(`group`)) AS group_count,
                COUNT(*) as jobs,
                SUM(wallt) AS wallt,
                ROUND(AVG(wallt)) AS avg_wallt,
                MAX(wallt) AS max_wallt,
                SUM(cput) AS cput,
                ROUND(AVG(cput)) AS avg_cput,
                MAX(cput) AS max_cput,
                ROUND(AVG(mem)) AS avg_mem,
                MAX(mem) AS max_mem,
                ROUND(AVG(vmem)) AS avg_vmem,
                MAX(vmem) AS max_vmem,
                ROUND(AVG(wait)) AS avg_wait,
                ROUND(AVG(exect)) AS avg_exect,
                ROUND(AVG(nodes)) AS avg_nodes,
                MAX(nodes) AS max_nodes,
                ROUND(AVG(cpus)) AS avg_cpus,
                MAX(cpus) AS max_cpus
            FROM event e
            WHERE date_key BETWEEN ? AND ?
            GROUP BY cluster, user
        },
        { Slice => {} },
        @$interval{qw( start end )}
    );
}

sub _select_cpu_consumption {
    my ( $self, $params ) = @_;

    my $sql = q{
        SELECT SUM(cput) AS cput
        FROM event
        WHERE cluster = ?
        AND date_key BETWEEN ? AND ?
        AND cpus >= ?
    };
    my @params = @$params{qw( cluster start end min )};

    if ( defined $params->{max} ) {
        $sql .= q{ AND cpus <= ? };
        push @params, $params->{max};
    }

    return $self->{dbh}->selectrow_hashref( $sql, { Slice => {} }, @params );
}

sub _select_actual_wait_time {
    my ( $self, $params ) = @_;

    my $sql = q{
        SELECT ROUND(AVG(wait)) AS avg_wait
        FROM event
        WHERE cluster = ?
        AND date_key BETWEEN ? AND ?
        AND cpus >= ?
    };
    my @params = @$params{qw( cluster start end min )};

    if ( defined $params->{max} ) {
        $sql .= q{ AND cpus <= ? };
        push @params, $params->{max};
    }

    return $self->{dbh}->selectrow_hashref( $sql, { Slice => {} }, @params );
}

sub _insert_cluster {
    my ( $self, $cluster ) = @_;

    my $sth = $self->{dbh}->prepare(q{ INSERT INTO cluster SET host = ? });
    $sth->execute( $cluster->{cluster} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_queue {
    my ( $self, $queue ) = @_;

    my $sth = $self->{dbh}->prepare(q{ INSERT INTO queue SET queue = ? });
    $sth->execute( $queue->{queue} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_group {
    my ( $self, $group ) = @_;

    my $sth = $self->{dbh}
        ->prepare(q{ INSERT INTO research_group SET group_name = ? });
    $sth->execute( $group->{group_name} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_user {
    my ( $self, $user ) = @_;

    my $sth = $self->{dbh}->prepare(q{ INSERT INTO user SET user = ? });
    $sth->execute( $user->{user} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_interval {
    my ( $self, $interval ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO time_interval SET
                time_interval = ?,
                start = ?,
                end = ?
        }
    );
    $sth->execute( @$interval{qw( label start end )} );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_activity {
    my ( $self, $activity ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO activity SET
                jobs = ?,
                wallt = ?,
                avg_wallt = ?,
                max_wallt = ?,
                cput = ?,
                avg_cput = ?,
                max_cput = ?,
                avg_mem = ?,
                max_mem = ?,
                avg_vmem = ?,
                max_vmem = ?,
                avg_wait = ?,
                avg_exect = ?,
                avg_nodes = ?,
                max_nodes = ?,
                avg_cpus = ?,
                max_cpus = ?
        }
    );
    $sth->execute(
        @$activity{
            qw(
                jobs
                wallt
                avg_wallt
                max_wallt
                cput
                avg_cput
                max_cput
                avg_mem
                max_mem
                avg_vmem
                max_vmem
                avg_wait
                avg_exect
                avg_nodes
                max_nodes
                avg_cpus
                max_cpus
                )
            }
    );

    return $self->{dbh}->{mysql_insertid};
}

sub _insert_cluster_activity {
    my ( $self, $activity ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO cluster_activity SET
                user_count = ?,
                group_count = ?,
                cluster_id = ?,
                activity_id = ?,
                interval_id = ?
        }
    );
    $sth->execute(
        @$activity{
            qw(
                user_count
                group_count
                cluster_id
                activity_id
                interval_id
                )
            }
    );
}

sub _insert_queue_activity {
    my ( $self, $activity ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO queue_activity SET
                user_count = ?,
                group_count = ?,
                cluster_id = ?,
                activity_id = ?,
                queue_id = ?,
                interval_id = ?
        }
    );
    $sth->execute(
        @$activity{
            qw(
                user_count
                group_count
                cluster_id
                activity_id
                queue_id
                interval_id
                )
            }
    );
}

sub _insert_group_activity {
    my ( $self, $activity ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO group_activity SET
                user_count = ?,
                cluster_id = ?,
                activity_id = ?,
                group_id = ?,
                interval_id = ?
        }
    );
    $sth->execute(
        @$activity{
            qw(
                user_count
                cluster_id
                activity_id
                group_id
                interval_id
                )
            }
    );
}

sub _insert_user_activity {
    my ( $self, $activity ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO user_activity SET
                cluster_id = ?,
                activity_id = ?,
                user_id = ?,
                interval_id = ?
        }
    );
    $sth->execute(
        @$activity{
            qw(
                cluster_id
                activity_id
                user_id
                interval_id
                )
            }
    );
}

sub _insert_cpu_consumption {
    my ( $self, $activity ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO cpu_consumption SET
                interval_id = ?,
                cluster_id = ?,
                label = ?,
                view_order = ?,
                cput = ?
        }
    );
    $sth->execute(
        @$activity{
            qw(
                interval_id
                cluster_id
                label
                view_order
                cput
                )
            }
    );
}

sub _insert_actual_wait_time {
    my ( $self, $activity ) = @_;

    my $sth = $self->{dbh}->prepare(
        q{
            INSERT INTO actual_wait_time SET
                interval_id = ?,
                cluster_id = ?,
                label = ?,
                view_order = ?,
                avg_wait = ?
        }
    );
    $sth->execute(
        @$activity{
            qw(
                interval_id
                cluster_id
                label
                view_order
                avg_wait
                )
            }
    );
}

sub _insert_queue_cluster {
    my ( $self, $keys ) = @_;

    $self->{dbh}
        ->do( q{ INSERT INTO queue_cluster SET queue_id = ?, cluster_id = ? },
        undef, @$keys{qw( queue_id cluster_id )} );
}

sub _insert_group_cluster {
    my ( $self, $keys ) = @_;

    $self->{dbh}
        ->do( q{ INSERT INTO group_cluster SET group_id = ?, cluster_id = ? },
        undef, @$keys{qw( group_id cluster_id )} );
}

sub _insert_user_cluster {
    my ( $self, $keys ) = @_;

    $self->{dbh}
        ->do( q{ INSERT INTO user_cluster SET user_id = ?, cluster_id = ? },
        undef, @$keys{qw( user_id cluster_id )} );
}

sub _insert_user_group {
    my ( $self, $keys ) = @_;

    $self->{dbh}
        ->do( q{ INSERT INTO user_group SET user_id = ?, group_id = ? },
        undef, @$keys{qw( user_id group_id )} );
}

sub _insert_user_queue {
    my ( $self, $keys ) = @_;

    $self->{dbh}
        ->do( q{ INSERT INTO user_queue SET user_id = ?, queue_id = ? },
        undef, @$keys{qw( user_id queue_id )} );
}

sub _delete_queue_cluster {
    my ( $self, $keys ) = @_;

    $self->{dbh}->do(
        q{ DELETE FROM queue_cluster WHERE queue_id = ? AND cluster_id = ? },
        undef, @$keys{qw( queue_id cluster_id )}
    );
}

sub _delete_group_cluster {
    my ( $self, $keys ) = @_;

    $self->{dbh}->do(
        q{ DELETE FROM group_cluster WHERE group_id = ? AND cluster_id = ? },
        undef, @$keys{qw( group_id cluster_id )}
    );
}

sub _delete_user_cluster {
    my ( $self, $keys ) = @_;

    $self->{dbh}->do(
        q{ DELETE FROM user_cluster WHERE user_id = ? AND cluster_id = ? },
        undef, @$keys{qw( user_id cluster_id )} );
}

sub _delete_user_group {
    my ( $self, $keys ) = @_;

    $self->{dbh}
        ->do( q{ DELETE FROM user_group WHERE user_id = ? AND group_id = ? },
        undef, @$keys{qw( user_id group_id )} );
}

sub _delete_user_queue {
    my ( $self, $keys ) = @_;

    $self->{dbh}
        ->do( q{ DELETE FROM user_queue WHERE user_id = ? AND queue_id = ? },
        undef, @$keys{qw( user_id queue_id )} );
}

sub _truncate_activity {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE activity });
}

sub _truncate_cluster_activity {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE cluster_activity });
}

sub _truncate_queue_activity {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE queue_activity });
}

sub _truncate_group_activity {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE group_activity });
}

sub _truncate_user_activity {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE user_activity });
}

sub _truncate_interval {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE time_interval });
}

sub _truncate_cpu_consumption {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE cpu_consumption });
}

sub _truncate_actual_wait_time {
    my ($self) = @_;
    $self->{dbh}->do(q{ TRUNCATE actual_wait_time });
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
