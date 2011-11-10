#!/usr/bin/env perl
use strict;
use warnings;
use Carp;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use File::Copy;

sub main {
    my $name    = 'ubmod';
    my $version = '0.2.0';

    my $full_name = "$name-$version";

    my $src = "$FindBin::Bin/..";

    deploy_assets($src);

    my $tempdir = tempdir( CLEANUP => 1 );
    my $dest = "$tempdir/$full_name";

    rcopy( $src, $dest, ignored($src) );

    my $tar = "$src/$full_name.tar.gz";

    create_tar( $tempdir, $full_name, $tar );
}

sub deploy_assets {
    my ($src) = @_;
    qx($src/portal/assets/setup.sh);
}

sub create_tar {
    my ( $base, $src, $tar ) = @_;
    qx(tar zcvf $tar -C $base $src);
}

sub ignored {
    my ($dir) = @_;

    my @files = map {"^$dir/$_\$"} qw(
        build
        shredder/config/settings\.ini
        portal/assets
        portal/config/settings\.ini
        ubmod-.*\.tar.gz
    );

    return \@files;
}

# Recursively copy a directory or file.
sub rcopy {
    my ( $src, $dest, $ignore ) = @_;

    return if grep { $src =~ $_ } @$ignore;

    if ( -f $src ) {
        my $perm = ( stat $src )[2];
        copy $src, $dest or croak "Copy failed: $!";
        chmod $perm, $dest
            or croak "Failed to change permissions of file '$dest': $!";
    }
    elsif ( -d $src ) {
        return copy_dir( $src, $dest, $ignore );
    }
    else {
        croak "Attempt to copy unexpected non-file: '$src'";
    }
}

# Copy a directory, recursively.
sub copy_dir {
    my ( $src, $dest, $ignore ) = @_;

    if ( !-d $dest ) {
        my $perm = ( stat $src )[2];
        make_path $dest or croak "Failed to create directory: '$dest': $!";
        chmod $perm, $dest
            or croak "Failed to change permissions of directory '$dest': $!";
    }

    opendir my ($dh), $src or croak "Failed to open directory '$src': $!";

    while ( my $file = readdir $dh ) {
        next if $file =~ /^\./;

        rcopy( "$src/$file", "$dest/$file", $ignore );
    }
}

main(@ARGV);
