#!/usr/bin/env perl
use strict;
use warnings;
use Getopt::Long;
use File::Copy;
use File::Path qw(make_path);
use FindBin;

my %options = (
    prefix  => '',
    destdir => '',
);

GetOptions(
    'prefix=s'     => \$options{prefix},
    'destdir=s'    => \$options{destdir},
    'bindir=s'     => \$options{bindir},
    'sysconfdir=s' => \$options{sysconfdir},
    'datadir=s'    => \$options{datadir},
    'docdir=s'     => \$options{docdir},
) or exit 1;

main( \%options );

exit;

sub main {
    my ($options) = @_;

    my $name    = 'ubmod';
    my $version = '0.2.5';

    my %dirs = (
        src  => $FindBin::Bin,
        dest => $options->{destdir},
        bin  => $options->{bindir},
        conf => $options->{sysconfdir},
        data => $options->{datadir},
        doc  => $options->{docdir},
    );

    if ( $options->{prefix} ) {
        $dirs{conf}  ||= "$options->{prefix}/etc";
        $dirs{bin}   ||= "$options->{prefix}/bin";
        $dirs{data}  ||= "$options->{prefix}/share";
        $dirs{doc}   ||= "$options->{prefix}/doc";
        $dirs{httpd} ||= "$options->{prefix}/etc/httpd";
    }
    else {
        $dirs{conf}  ||= "/etc/$name";
        $dirs{bin}   ||= '/usr/bin';
        $dirs{data}  ||= "/usr/share/$name";
        $dirs{doc}   ||= "/usr/share/doc/$name-$version";
        $dirs{httpd} ||= '/etc/httpd/conf.d';
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
                or die "Failed to create directory: '$dirs->{$key}': $!";
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

    open my ($fh), '<', $src or die "Failed to open file '$src': $!";
    my $perm = ( stat $fh )[2];
    my $text = do { local $/; <$fh> };
    close $fh or die "Failed to close file '$src': $!";
    undef $fh;

    while ( my ( $key, $value ) = each %$substitutions ) {
        $text =~ s/\Q$key\E/$value/g;
    }

    open $fh, '>', $dest or die "Failed to open file '$dest': $!";
    print $fh $text or die "Failed to write to file '$dest': $!";
    chmod $perm, $fh
        or die "Failed to change permissions of file '$dest': $!";
}

sub templates {
    my ($dirs) = @_;

    return {
        'docs/examples/roles.json'      => "$dirs->{conf}/roles.json",
        'docs/examples/settings.ini'    => "$dirs->{conf}/settings.ini",
        'docs/examples/ubmod.conf'      => "$dirs->{httpd}/ubmod.conf",
        'docs/examples/user-roles.json' => "$dirs->{conf}/user-roles.json",
        'portal/config/constants.php'   => "$dirs->{conf}/constants.php",
        'shredder/bin/ubmod-shredder'   => "$dirs->{bin}/ubmod-shredder",
        'shredder/bin/ubmod-slurm-helper' =>
            "$dirs->{bin}/ubmod-slurm-helper",
        'shredder/lib/Ubmod/Config.pm' => "$dirs->{perllib}/Ubmod/Config.pm",
        'portal/html/index.php'        => "$dirs->{html}/index.php",
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
        "BASE_DIR . '/lib'"    => qq['$dirs->{phplib}'],
        "BASE_DIR . '/config'" => qq['$dirs->{conf}'],
        "define('BASE_DIR', dirname(dirname(__FILE__)))" =>
            qq[define('BASE_DIR', '$dirs->{data}')],

        # Directories used in index.php files.
        "dirname(__FILE__) . '/../config"       => qq['$dirs->{conf}],
        "dirname(__FILE__) . '/../../../config" => qq['$dirs->{conf}],
    };
}

sub file_map {
    my ($dirs) = @_;

    return {
        'portal/config/acl-resources.json' =>
            "$dirs->{conf}/acl-resources.json",
        'portal/config/acl-roles.json' => "$dirs->{conf}/acl-roles.json",
        'portal/config/bootstrap.php'  => "$dirs->{conf}/bootstrap.php",
        'portal/config/datawarehouse.json' =>
            "$dirs->{conf}/datawarehouse.json",
        'portal/config/menu.json'   => "$dirs->{conf}/menu.json",
        'portal/config/palette.csv' => "$dirs->{conf}/palette.csv",
        'portal/html'               => $dirs->{html},
        'portal/templates'          => $dirs->{templates},
        'portal/lib'                => $dirs->{phplib},
        'shredder/lib'              => $dirs->{perllib},
        AUTHORS                     => "$dirs->{doc}/AUTHORS",
        ChangeLog                   => "$dirs->{doc}/ChangeLog",
        INSTALL                     => "$dirs->{doc}/INSTALL",
        LICENSE                     => "$dirs->{doc}/LICENSE",
        NOTICE                      => "$dirs->{doc}/NOTICE",
        README                      => "$dirs->{doc}/README",
        ddl                         => "$dirs->{doc}/ddl",
        docs                        => "$dirs->{doc}/docs",
    };
}

# Recursively copy a directory or file.
sub rcopy {
    my ( $src, $dest ) = @_;

    if ( -f $src ) {
        my $perm = ( stat $src )[2];
        copy $src, $dest or die "Copy failed: $!";
        chmod $perm, $dest
            or die "Failed to change permissions of file '$dest': $!";
    }
    elsif ( -d $src ) {
        copy_dir( $src, $dest );
    }
    else {
        die "Attempt to copy unexpected non-file: '$src'";
    }
}

# Copy a directory, recursively.
sub copy_dir {
    my ( $src, $dest ) = @_;

    if ( !-d $dest ) {
        my $perm = ( stat $src )[2];
        make_path $dest or die "Failed to create directory: '$dest': $!";
        chmod $perm, $dest
            or die "Failed to change permissions of directory '$dest': $!";
    }

    opendir my ($dh), $src or die "Failed to open directory '$src': $!";

    while ( my $file = readdir $dh ) {

        # Skip hidden files
        next if $file =~ /^\./;

        rcopy( "$src/$file", "$dest/$file" );
    }
}

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

