<?php
#MySQL Klasse zur 'einfachen' MySQL Datenbankabfrage in php

function destroy($target)		{	#Objekt aus Speicher loeschen
	$$target="NULL";
	unset($$target);	
}

class verbindung			{	#Baut Datenbankverbindung auf und fuehrt Queries durch
	#Benoetigte Variablen:
	var $my_db_user;#MySQL user
	var $my_db_pass;#MySQL passwort
	var $my_db_host;#MySQL host
	var $my_db_db;	#MySQL Datenbank
	#Optional aber wichtig ;)
	var $frage; 	#durchzuführende abfrage (wird von funktion erstellt oder einfach selbst definiert!)
	#Rückgabe
	var $antwort;	#die Rückgabevariable
	
	#Interne Variablen
	var $cache; 	#zwischenspeicher für die antwortarrays
	var $my_connection; #verbindungskennung

	# Verbindungs Funktionen - Datenbankverbindung aufbauen / abbauen
	#
	#function aufbauen($host=$this->my_db_host,$user=$this->my_db_user,$passwd=$this->my_db_pass,$db=$this->my_db_db)		{	#funktioniert nich :(

	function aufbauen($host="localhost",$user,$passwd="",$db="")		{	#Verbindung aufbauen
		$this->my_db_host=$host;
		$this->my_db_user=$user;
		$this->my_db_pass=$passwd;
		$this->my_db_db=$db;
		
		$this->my_connection=mysql_connect($this->my_db_host,$this->my_db_user,$this->my_db_pass) or die('du du du ... Kein Anschluss unter dieser Datenbank ... du du du :p');
			#$debug_blog.='SQL \t connect \t hi!.\n';
		mysql_select_db($this->my_db_db,$this->my_connection) or die('Datenbank futsch?<hr>'.mysql_error()."");
			#$debug_blog.='SQL \t Datenbank \t gefunden.\n';
	}
	function trennen()		{	#Verbindung trennen
			mysql_close($this->my_connection) or die('DB too close... ...?');
				#$debug_blog.='SQL \t close \t bye.\n';
	}
	
	# Abfragefunktionen
	#
	function fragen($frage)		{	#Fuehrt die Abfrage durch
		$this->frage=$frage;
		#$debug_blog. "<hr>abfrage: ".$this->frage."<hr>";
		$this->cache=mysql_query($this->frage) or die("W A S  willst du? ... ".mysql_error()."");
		$tmp="";
		$zeile=0;
		
		while($tmp = mysql_fetch_array($this->cache)){ #das abfrageergebnis wird in einem zweidimensionalen array bereitgestellt in der form: antwort[Zeilennummer][Spaltennummer]
			for($spalte=0; $spalte<count($tmp)/2; $spalte++) {#^^warum bekommt die count funktion den doppelten wert raus? werden unterarrays mitgezählt? komisch. erstmal mit /2 gefixt.
				$this->antwort[$zeile][$spalte]=$tmp[$spalte];
			}
			$zeile++;
		} 
		#$debug_blog.='SQL \t query \t ok.\n';
		return $this->antwort[0][0];
	}
	
	# Funktionen ohne Rueckgabewerte ausfuehren
	#	
	function mach($frage) {
		$this->frage=$frage;
		#$debug_blog.="<hr>Abfrage: ".$this->frage."<hr>";
		mysql_query($this->frage) or die("Verstopfung ... ".mysql_error()."");
	}
	
}# Ende Klassendefinition "Verbindung"

class abfrage extends verbindung {	#Erstellt SQL Abfrage 	
	#Verfuegbare Variblen
	var $my_tabelle; 	#zu durchsuchende Tabelle
	var $my_spalte; 	#anzuzeigende Spalte
	var $my_search_wo;	#zu durchsuchende Spalte (id oder so...)
	var $my_search_was;	#zu suchendes Objekt
	var $my_search_operator;	# search =, like, ><, is not	
	#Fuer Abfragen
	var $my_tabelle2;	#2. Tabelle für Tabellenübergreifende Abfragen
	var $my_spalte2; 	#2. Spalte für übergreifende Abfragen	
	var $my_sort_dir;	#sortierung absteigend oder aufsteigend DESC oder ASC
	var $my_sort_by;	#sortierung nach welchem Feld
	var $my_group;		#gruppierung nach welchem Feld
	var $my_opt; 		#2. auszuführende funktion (z.b. count) 
	#Fuer Manipulationen (Einfuegen/Aendern()
	var $my_value;
	
	# Abfragefunktionen
	#
	function zeigmir($tabelle, $spalte="*", $wo="", $was="", $sort_by="", $sort_dir="")		{ #Select Abfrage zusammenbauen
		if($tabelle) 	{ $this->my_tabelle=$tabelle; }
		if($spalte) 	{ $this->my_spalte=$spalte; }
		if($wo) 	{ $this->my_search_wo=$wo; }
		if($was) 	{ $this->my_search_was=$was; }
		if($sort_by)	{ $this->my_sort_by=$sort_by; }
		if($sort_dir)	{ $this->my_sort_dir=$sort_dir; }
		
		$this->frage="SELECT";
		
		if($this->my_spalte)	{ $this->frage.=" ".$this->my_spalte.""; }
		else			{ $this->frage.=" *"; }
		if($this->my_opt) 	{ $this->frage.="".$this->my_opt.""; }
		
		$this->frage.=" FROM ".$this->my_tabelle."";

		if(!$this->my_search_operator)	{ $this->my_search_operator=" = "; } # LIKE	
		if($this->my_search_wo and $this->my_search_was){ $this->frage.=" WHERE ".$this->my_search_wo.$this->my_search_operator."'".$this->my_search_was."'"; }
		if($this->my_tabelle2 and $this->my_spalte2) 	{ $this->frage.=" INNER JOIN ".$this->my_tabelle2." ON ".$this->my_tabelle.".".$this->my_spalte."=".$this->my_tabelle2.".".$this->my_spalte2.""; }
		if($this->my_group) 	{ $this->frage.=" GROUP BY ".$this->my_group.""; }
		if($this->my_sort_by) 	{ $this->frage.=" ORDER BY ".$this->my_sort_by.""; }
		if($this->my_sort_dir) 	{ $this->frage.=" ".$this->my_sort_dir.""; }

		$this->frage.=";";
		#$debug_bog.="Abfrage: ".$this->frage."<hr>";
		return verbindung::fragen($this->frage);
	}
	function machneu($tabelle, $spalte="", $value="")	{ #Neuen Datensatz anlegen
		$this->my_tabelle=$tabelle;
		$this->my_spalte=$spalte;
		$this->my_value=$value;
		
		$this->frage="INSERT INTO ".$this->my_tabelle;
		if($this->my_spalte) { $this->frage.="(".$this->my_spalte.") "; } 
		$this->frage.="VALUES(".$this->my_value.")";
		verbindung::mach($this->frage);
	}
	function machanders($tabelle,$value, $wo, $was)		{ #Datensatz aendern
		$this->my_tabelle=$tabelle;
		$this->my_value=$value; #in der form "spalte='wert',spalte2='wert2'" angeben!
		$this->my_search_wo=$wo;
		$this->my_search_was=$was;
		#funktionsaufruf  machanders($tabelle,"spalte='wert',spalte2='wert',...",$wo,$was) .
		$this->frage="UPDATE ".$this->my_tabelle." SET ".$this->my_value."";
		if($this->my_search_wo) { $this->frage.=" WHERE ".$this->my_search_wo."=".$this->my_search_was.""; }
		$this->frage.=";";
		verbindung::mach($this->frage);
	}
	function machweg($tabelle, $wo, $was)				{ #Datensatz loeschen
		$this->tabelle=$tabelle; 
		$this->search_wo=$wo;
		$this->search_was=$was;
		
		$this->frage="DELETE FROM ".$this->tabelle." WHERE ";
		$this->frage.=$this->search_wo."='".$this->search_was."'"; 
		verbindung::mach($this->frage);		
	}
			
}# Ende Klassendefinition "Abfrage"
	

?>
