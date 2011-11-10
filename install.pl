#!/usr/bin/env perl
use strict;
use warnings;
use Carp;
use Getopt::Long qw(GetOptionsFromArray);
use File::Copy;
use File::Path qw(make_path);
use FindBin;

sub main {
    my $name    = 'ubmod';
    my $version = '0.2.0';

    my $prefix  = '';
    my $destdir = '';

    my ( $bindir, $sysconfdir, $datadir, $docdir );

    GetOptionsFromArray(
        \@_,
        'prefix=s'     => \$prefix,
        'destdir=s'    => \$destdir,
        'bindir=s'     => \$bindir,
        'sysconfdir=s' => \$sysconfdir,
        'datadir=s'    => \$datadir,
        'docdir=s'     => \$docdir,
    ) or croak "Invalid options";

    my %dirs = (
        src  => $FindBin::Bin,
        dest => $destdir,
        bin  => $bindir,
        conf => $sysconfdir,
        data => $datadir,
        doc  => $docdir,
    );

    if ($prefix) {
        $dirs{conf}  ||= "$prefix/etc";
        $dirs{bin}   ||= "$prefix/bin";
        $dirs{data}  ||= "$prefix/share";
        $dirs{doc}   ||= "$prefix/doc";
        $dirs{httpd} ||= "$prefix/etc/httpd";
    }
    else {
        $dirs{conf}  ||= "/etc/$name";
        $dirs{bin}   ||= '/usr/bin';
        $dirs{data}  ||= "/usr/share/$name";
        $dirs{doc}   ||= "/usr/share/doc/$name-$version";
        $dirs{httpd} ||= '/etc/httpd/conf.d',;
    }

    $dirs{phplib}    = "$dirs{data}/php5/lib";
    $dirs{perllib}   = "$dirs{data}/perl5/lib";
    $dirs{html}      = "$dirs{data}/html";
    $dirs{templates} = "$dirs{data}/templates";

    install( \%dirs );
}

sub install {
    my ($dirs) = @_;

    my $subs = substitutions($dirs);

    # Prepend destination directory.
    for my $key ( keys %$dirs ) {

        # Skip the source and destination.
        next if $key eq 'src';
        next if $key eq 'dest';

        $dirs->{$key} = "$dirs->{dest}$dirs->{$key}";

        # Create directories that don't exist.
        if ( !-d $dirs->{$key} ) {
            make_path $dirs->{$key}
                or croak "Failed to create directory: '$dirs->{$key}': $!";
        }
    }

    my $files     = file_map($dirs);
    my $templates = templates($dirs);

    install_files($files);
    install_templates( $templates, $subs );
}

sub install_files {
    my ($files) = @_;

    while ( my ( $src, $dest ) = each %$files ) {
        rcopy( $src, $dest );
    }
}

sub install_templates {
    my ( $templates, $substitutions ) = @_;

    while ( my ( $src, $dest ) = each %$templates ) {
        install_template( $src, $dest, $substitutions );
    }
}

sub install_template {
    my ( $src, $dest, $substitutions ) = @_;

    open my ($fh), '<', $src or croak "Failed to open file '$src': $!";
    my $perm = ( stat $fh )[2];
    my $text = do { local $/; <$fh> };
    close $fh or croak "Failed to close file '$src': $!";
    undef $fh;

    while ( my ( $key, $value ) = each %$substitutions ) {
        $text =~ s/\Q$key\E/$value/g;
    }

    open $fh, '>', $dest or croak "Failed to open file '$dest': $!";
    print $fh $text or croak "Failed to write to file '$dest': $!";
    chmod $perm, $fh
        or croak "Failed to change permissions of file '$dest': $!";
}

sub templates {
    my ($dirs) = @_;

    return {
        'docs/ubmod.conf'             => "$dirs->{httpd}/ubmod.conf",
        'docs/settings.ini'           => "$dirs->{conf}/settings.ini",
        'portal/config/constants.php' => "$dirs->{conf}/constants.php",
        'shredder/bin/ubmod-shredder' => "$dirs->{bin}/ubmod-shredder",
        'portal/html/index.php'       => "$dirs->{html}/index.php",
        'portal/html/api/rest/index.php' =>
            "$dirs->{html}/api/rest/index.php",
    };
}

sub substitutions {
    my ($dirs) = @_;

    return {

        # Replace use of FindBin in Perl code.
        "use FindBin;\n"          => '',
        '$FindBin::Bin/../lib'    => $dirs->{perllib},
        '$FindBin::Bin/../config' => $dirs->{conf},

        # Directory used by Apache configuration.
        '/usr/share/ubmod/html' => $dirs->{html},

        # Directories in constants.php file.
        'BASE_DIR . "/lib"'    => qq['$dirs->{phplib}'],
        'BASE_DIR . "/config"' => qq['$dirs->{conf}'],
        'define("BASE_DIR", dirname(dirname(__FILE__)))' =>
            qq[define('BASE_DIR', '$dirs->{data}')],

        # Directories used in index.php files.
        "dirname(__FILE__) . '/../config"       => qq['$dirs->{conf}],
        "dirname(__FILE__) . '/../../../config" => qq['$dirs->{conf}],
    };
}

sub file_map {
    my ($dirs) = @_;

    return {
        'portal/config/bootstrap.php' => "$dirs->{conf}/bootstrap.php",
        'portal/config/palette.csv'   => "$dirs->{conf}/palette.csv",
        'portal/config/datawarehouse.json' =>
            "$dirs->{conf}/datawarehouse.json",
        'portal/html'      => $dirs->{html},
        'portal/templates' => $dirs->{templates},
        'portal/lib'       => $dirs->{phplib},
        'shredder/lib'     => $dirs->{perllib},
        AUTHORS            => "$dirs->{doc}/AUTHORS",
        ChangeLog          => "$dirs->{doc}/ChangeLog",
        INSTALL            => "$dirs->{doc}/INSTALL",
        NOTICE             => "$dirs->{doc}/NOTICE",
        TODO               => "$dirs->{doc}/TODO",
        README             => "$dirs->{doc}/README",
        ddl                => "$dirs->{doc}/ddl",
        docs               => "$dirs->{doc}/docs",
    };
}

# Recursively copy a directory or file.
sub rcopy {
    my ( $src, $dest ) = @_;

    if ( -f $src ) {
        my $perm = ( stat $src )[2];
        copy $src, $dest or croak "Copy failed: $!";
        chmod $perm, $dest
            or croak "Failed to change permissions of file '$dest': $!";
    }
    elsif ( -d $src ) {
        copy_dir( $src, $dest );
    }
    else {
        croak "Attempt to copy unexpected non-file: '$src'";
    }
}

# Copy a directory, recursively.
sub copy_dir {
    my ( $src, $dest ) = @_;

    if ( !-d $dest ) {
        my $perm = ( stat $src )[2];
        make_path $dest or croak "Failed to create directory: '$dest': $!";
        chmod $perm, $dest
            or croak "Failed to change permissions of directory '$dest': $!";
    }

    opendir my ($dh), $src or croak "Failed to open directory '$src': $!";

    while ( my $file = readdir $dh ) {

        # Skip hidden files
        next if $file =~ /^\./;

        rcopy( "$src/$file", "$dest/$file" );
    }
}

main(@ARGV);
