#!perl -T
use strict;
use warnings;
use Test::More;
use Test::MockObject;
use Ubmod::Shredder::Sge;

my $mock_logger = Test::MockObject->new();

my $shredder = Ubmod::Shredder::Sge->new( logger => $mock_logger );

my @memory_pairs = (
    [ '1024',  1 ],
    [ '2048',  2 ],
    [ '1024b', 1 ],
    [ '2048b', 2 ],
    [ '1k',    0 ],
    [ '2k',    1 ],
    [ '1K',    1 ],
    [ '2K',    2 ],
    [ '1m',    int( 1000 * 1000 / 1024 ) ],
    [ '1M',    1024 ],
    [ '1g',    int( 1000 * 1000 * 1000 / 1024 ) ],
    [ '1G',    1024 * 1024 ],
    [ '1.5G',  1.5 * 1024 * 1024 ],
);

for my $mem_pair (@memory_pairs) {
    is( $shredder->_parse_memory( $mem_pair->[0] ), $mem_pair->[1] );
}

done_testing();

