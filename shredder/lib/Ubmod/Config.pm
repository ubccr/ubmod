package Ubmod::Config;
use strict;
use warnings;
use FindBin;
use Config::Tiny;

my $config;
my $config_file = "$FindBin::Bin/../config/settings.ini";

sub get_config {
    if ( !defined $config ) {
        $config = Config::Tiny->read($config_file);
    }

    return $config;
}

1;

