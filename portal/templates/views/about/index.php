<div style="margin-top:10px;">
  <div class="labelHeading" style="font-weight:bold;">
    About UBMoD - University at Buffalo Metrics on Demand
  </div>
  <br />
  UBMoD 2.0 (Version <?php echo UBMOD_VERSION ?>)
  <br />
  <br />
  <p>UBMoD (UB Metrics on Demand) is an open source data warehouse and web
  portal for mining statistical data from resource managers
  (<a href="http://www.clusterresources.com/pages/products/torque-resource-manager.php">TORQUE</a>,
  <a href="http://www.openpbs.org">OpenPBS</a>,
  <a href="http://gridscheduler.sourceforge.net">SGE</a> and
  <a href="http://slurm.schedmd.com/">Slurm</a>)
  commonly found in high-performance computing environments. It has been
  developed by the
  <a href="http://www.ccr.buffalo.edu">Center for Computational Research</a>
  at the <a href="http://www.buffalo.edu">University at Buffalo</a>, SUNY
  and presents resource utilization including CPU cycles consumed, total
  jobs, average wait time, etc. for individual users, research groups,
  departments, and decanal units. The web-based user interface provides a
  dashboard for displaying resource consumption along with fine-grained
  control over the time period and resources displayed. The data warehouse
  can easily be customized to support new resource managers. The information
  presented in easy-to-understand charts and tables and provides system
  administrators, users, and directors of HPC centers with a rich set of
  metrics to better understand how their resources are being utilized. The
  current release, which was completely re-written in PHP, adds the ability
  to apply custom tags to users and jobs and to then filter all reports
  using those tags. This provides complete flexibility for organizing users
  into departments, projects, and groups. For example, users can be tagged
  as members of one or more projects and reports can be dynamically
  generated for those projects.</p>
  <br />
  <p>The UBMoD frontend is written in PHP. It uses MySQL as the backend
  database and uses Perl for parsing resource manager accounting files. The
  web portal runs on the Apache HTTP server and can be viewed using any
  popular modern web browser that supports JavaScript.</p>
  <br />
  <p><strong>UBMoD</strong> is an open source project released under the GNU
  General Public License ("GPL") Version 3.0. You can obtain a copy of the
  license <a href="http://www.gnu.org/licenses/gpl-3.0.txt">here</a>.</p>
  <br />
  <p>Further details as well as the source code can be
  found at <a href="http://ubmod.sf.net">http://ubmod.sf.net</a></p>
</div>

