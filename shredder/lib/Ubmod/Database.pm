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

__END__

=head1 NAME

Ubmod::Database

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

