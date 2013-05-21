package Ubmod::Database;
use strict;
use warnings;
use Carp;
use DBI;
use Ubmod::Config;

my $dbh;

sub get_dbh {
    my ($class) = @_;

    if ( !defined $dbh ) {
        $dbh = $class->connect();
    }

    return $dbh;
}

sub connect {
    my ($class) = @_;

    my $config = Ubmod::Config->get_config();

    my $args = $config->{database};

    for my $arg (qw( host dbname user password )) {
        if ( !defined $args->{$arg} ) {
            croak "Missing database config option: '$arg'";
        }
    }

    my $dsn = "DBI:mysql:database=$args->{dbname};host=$args->{host}";

    if ( defined $args->{port} ) {
        $dsn .= ";port=$args->{port}";
    }

    my $dbh = eval {
        DBI->connect( $dsn, $args->{user}, $args->{password},
            { PrintError => 0, RaiseError => 1, AutoCommit => 1 } );
    };
    if ($@) {
        croak "Failed to connect to database: $@";
    }

    return $dbh;
}

1;

