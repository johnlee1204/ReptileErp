<?php
	class JenkinsDeploy extends JenkinsJob{
		function deploy(){
			

			// =================== ITX 169 =============================			
			Rsync::deploy(array(
				'server' => $this->sshServers['itx169'],
				'deployFolder' => 'inc',
				'exclusions' => array('php/models/credentials/*','php/phpunit-tests')
			));

			$incPathIntranet = "/cygdrive/c/netfiles/inc/php";
			// =================== Intranet =============================
			
			/*
			Rsync::deploy(array(
				'server' => $this->sshServers['intranet'],
				'source' => 'php/classes/',
				'deployFolder' => 'classes',
				'deployPath' => $incPathIntranet
			));

			Rsync::deploy(array(
				'server' => $this->sshServers['intranet'],
				'source' => 'php/libraries/',
				'deployFolder' => 'libraries',
				'deployPath' => $incPathIntranet
			));

			Rsync::deploy(array(
				'server' => $this->sshServers['intranet'],
				'source' => 'php/models/',
				'deployFolder' => 'models',
				'deployPath' => $incPathIntranet,
				'exclusions' => array('credentials/*')
			));
			*/
			
			return 0;
		}
	}
?>