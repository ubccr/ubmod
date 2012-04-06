Name:      ubmod
Version:   0.2.3
Release:   1%{?dist}
Summary:   Data warehouse and web portal for mining statistical data from resource managers
URL:       http://ubmod.sourceforge.net
Vendor:    Center for Computational Research, University at Buffalo
Packager:  Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
Group:     Applications/Internet
License:   GPLv3+
Source:    %{name}-%{version}.tar.gz
Patch:     ubmod-0.2.0-imagettftext.patch
BuildRoot: %(mktemp -ud %{_tmppath}/%{name}-%{version}-%{release}-XXXXXX)
BuildArch: noarch
Requires:  httpd
Requires:  mysql-server >= 5.1 mysql >= 5.1
Requires:  php >= 5.3 php-cli
Requires:  php-mysql php-gd php-pdo
Requires:  perl >= 5.10.1
Requires:  perl-Config-Tiny perl-DateTime perl-DBI perl-DBD-MySQL

%description
UBMoD is a data warehouse and web portal for mining statistical data
from resource managers in high-performance computing environments.
UBMoD presents resource utilization over set time periods and provides
detailed interactive charts, graphs, and tables.

%prep
%setup -q
%patch

%install
rm -rf $RPM_BUILD_ROOT
./install.pl --destdir=$RPM_BUILD_ROOT

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%doc %dir /usr/share/doc/%{name}-%{version}
%doc /usr/share/doc/%{name}-%{version}/AUTHORS
%doc /usr/share/doc/%{name}-%{version}/ChangeLog
%doc /usr/share/doc/%{name}-%{version}/INSTALL
%doc /usr/share/doc/%{name}-%{version}/LICENSE
%doc /usr/share/doc/%{name}-%{version}/NOTICE
%doc /usr/share/doc/%{name}-%{version}/TODO
%doc /usr/share/doc/%{name}-%{version}/README
%doc /usr/share/doc/%{name}-%{version}/docs
%doc /usr/share/doc/%{name}-%{version}/ddl
%config %dir /etc/ubmod
%config(noreplace) /etc/httpd/conf.d/ubmod.conf
%config(noreplace) /etc/ubmod/settings.ini
%config(noreplace) /etc/ubmod/palette.csv
%config /etc/ubmod/datawarehouse.json
%config /etc/ubmod/bootstrap.php
%config /etc/ubmod/constants.php
/usr/bin/ubmod-shredder
/usr/share/ubmod

%changelog
* Fri Apr 6 2012 Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu> 0.2.3-1
- Added unique key to sge_event table; this allows re-shredding the
  same log file without duplicate data being inserted into the
  database (Based on contribution from Scott Roberts)
- Added support for using num_procs consumable resource with SGE to
  specify number of cpus (Contributed by Scott Roberts)
- Added support for floating point memory values to shredders
- Updated documentation:
  - Added FAQ, shredder guide and tag guide
  - Removed outdated schema diagram
  - Updated database schema descriptions
- Added logchecker.sh.patch (Contributed by Scott Roberts)
- Moved SGE specific documentation to a separate directory
- Added host check to shredder when determining which files to process
- Added support for specifying the MySQL port number
- Fixed typos in shredder warning messages
- Fixed bug in stored procedure used to create timespan aggregate
* Tue Dec 20 2011 Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu> 0.2.2-1
- Minor documentation updates
- Changed time zone used in shredding process
- Added support for SGE project tags
* Wed Nov 30 2011 Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu> 0.2.1-1
- Minor documentation updates
- Removed dependence on Getopt::Long 2.38
* Fri Nov 11 2011 Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu> 0.2.0-1
- Initial RPM release

