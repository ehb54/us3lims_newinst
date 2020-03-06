<?php
/*
 * index.php
 *
 * main page 
 *
 */
session_start();

include 'config.php';

$page_title = "Welcome!";
$css = 'css/index.css';
include 'header.php';
?>
<div id='content'>

  <h1 class="title"><?php echo $org_name; ?></h1>

  <h3><em>This is where to request a new instance of LIMS3</em></h3>

  <p>Please enter a request for a new LIMS instance by clicking on the &ldquo;Request
     Instance,&rdquo; a button to the left.</p>

<p> Funding for this facility is provided through multiple sources:</p>

<ul>

  <li><a href='http://www.biochem.uthscsa.edu'>Department of Biochemistry</a>,
  <a href='http://www.uthscsa.edu'>University of Texas Health Science Center at
  San Antonio</a> </li>

  <li>User fees collected from collaborators and users of the <a
  href='http://www.cauma.uthscsa.edu'>UltraScan facility</a> at UTHSCSA.</li>

  <li>San Antonio Life Science Institute Grant #10001642</li>

  <li><a href='http://www.nsf.gov'>The National Science Foundation</a>, Grants
  DBI-9974819, ANI-228927, DBI-9724273, TG-MCB070038 (all to Borries Demeler)</li>

  <li><a href='http://www.nih.gov'>The National Institutes of Health</a>, Grant NCRR-R01RR022200 (to Borries Demeler)</li>

</ul>
<p> When publishing, please credit our facility as follows:</p>
<ul>
<p>
<b>
Calculations were performed on the UltraScan LIMS cluster at the<br/>
Bioinformatics Core Facility at the University of Texas Health<br/>
Science Center at San Antonio and the Lonestar cluster at the<br/>
Texas Advanced Computing Center supported by NSF Teragrid Grant<br/>
#MCB070038 (to Borries Demeler)."</p>

</b>
</ul>

<p>Please forward the link to each manuscript citing our facility to
<a href='mailto:demeler@biochem.uthscsa.edu'>demeler@biochem.uthscsa.edu</a></p>


<p> Thank you for visiting and feel free to send us your comments!</p>

<p><a href='mailto:demeler@biochem.uthscsa.edu'>Borries Demeler, Ph.D.</a><br/>
Associate Professor<br/>
Facility Director</p>

</div>

<?php
include 'footer.php';
?>
