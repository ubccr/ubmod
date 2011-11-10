Name:      ubmod
Version:   0.2.0
Release:   1%{?dist}
Summary:   Data warehouse and web portal for mining statistical data from resource managers
URL:       http://ubmod.sourceforge.net
Vendor:    Center for Computational Research, University at Buffalo
Packager:  Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
Group:     Applications/Internet
License:   GPLv3+
Source:    %{name}-%{version}.tar.gz
BuildRoot: %(mktemp -ud %{_tmppath}/%{name}-%{version}-%{release}-XXXXXX)
BuildArch: noarch
Requires:  httpd
Requires:  mysql-server >= 5.1 mysql >= 5.1
Requires:  php >= 5.3
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

%install
rm -rf $RPM_BUILD_ROOT
./install.pl --destdir=$RPM_BUILD_ROOT

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%doc /usr/share/doc/%{name}-%{version}/AUTHORS
%doc /usr/share/doc/%{name}-%{version}/ChangeLog
%doc /usr/share/doc/%{name}-%{version}/INSTALL
%doc /usr/share/doc/%{name}-%{version}/NOTICE
%doc /usr/share/doc/%{name}-%{version}/TODO
%doc /usr/share/doc/%{name}-%{version}/README
%doc /usr/share/doc/%{name}-%{version}/docs
%doc /usr/share/doc/%{name}-%{version}/ddl
%config(noreplace) /etc/httpd/conf.d/ubmod.conf
%config(noreplace) /etc/ubmod/settings.ini
%config(noreplace) /etc/ubmod/palette.csv
/etc/ubmod/datawarehouse.json
/etc/ubmod/bootstrap.php
/etc/ubmod/constants.php
/usr/bin/ubmod-shredder
/usr/share/ubmod

%changelog
* Tue Oct 31 2011 Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu> 0.2.0-1
- Initial RPM release
