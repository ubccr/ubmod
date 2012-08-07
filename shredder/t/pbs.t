#!perl -T
use strict;
use warnings;
use Test::More;
use Test::MockObject;
use Ubmod::Shredder::Pbs;

my $mock_logger = Test::MockObject->new();

my $shredder = Ubmod::Shredder::Pbs->new( logger => $mock_logger );

my @memory_pairs = (
    [ '1024',  1024 ],
    [ '2048',  2048 ],
    [ '1024b', 1 ],
    [ '2048b', 2 ],
    [ '1kb',   1 ],
    [ '2kb',   2 ],
    [ '1mb',   1024 ],
    [ '2mb',   2048 ],
    [ '1gb',   1024 * 1024 ],
    [ '1.5gb', 1.5 * 1024 * 1024 ],
);

for my $mem_pair (@memory_pairs) {
    is( $shredder->_parse_memory( $mem_pair->[0] ), $mem_pair->[1] );
}

done_testing();

