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
    my $version = '0.2.4';

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

__END__

=pod

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

