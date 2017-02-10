<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'yab_download';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.7';
$plugin['author'] = 'Tommy Schmucker';
$plugin['author_uri'] = 'http://www.yablo.de/';
$plugin['description'] = 'Allows you to offer a download of multiple files with or without a confirmation button and an option to zip all files on the fly.';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '';

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
	h1, h2, h3
	h1 code, h2 code, h3 code {
		margin-bottom: 0.6em;
		font-weight: bold
	}
	h1 {
		font-size: 1.4em
	}
	h2 {
		font-size: 1.25em
	}
	h3 {
		margin-bottom: 0;
		font-size: 1.1em
	}
	table {
		margin-bottom: 1em
	}
</style>
# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
h1. yab_download

This plugin allows you to offer a download of multiple files (per file id and/or category) with or without a confirmation button. Additional you can offer an option to download all the files as zip archive on the fly. The names of the files are read from the files description. If no description is given the filenames are displayed.

h2. Usage

This plugin has only one tag: @<txp:yab_download />@. You have to place it in an individual article.
The following attributes are available:

* *files*: The IDs of the files, which are for download (seperated by comma). If no files assigned with an ID an error message will displayed in the option field.
* *label1*: Label for select
* *label2*: Label for confirmation checkbox
* *label3*: Label for the submit input
* *label4*: Label for zip download checkbox
* *notice*: Error notice, if confirmation is not set but required
* *size*: Display filesize additionally (values are B, KB,MB, GB,PB).
* *confirm* : Form used with a confirmation checkbox (values: '1' or some other string)
* *formid*: html id of the element form, used for anchor, if you use more than one <txp:yab_download /> in a single site, make different entries here; (default @yab_cd_download@)
* *cats*: File categories you will add to download (seperated by comma); will additional displayed with files from @files@ attribute above
* *exclude*: IDs of the files you will exclude; Does only work with @cats@ attribute.
* *sort*: Sort the files from @cats@; (defaul @description asc@)
* *zip*: Offfers an checkbox with an option to download all offered files as zip instead (values: '1' for zip creation; '2' to force zip creation in memory)
* *zipname*: the name of the offerd zip (default all.zip)
* *hidelist*: hides the select list of the files, usefull if you offer only one file or offer many files zipped without a seletion (values: '1')
* *hidezip*: hides the zip checkbox (download as zip is forced), useful if you offer only one file as zip or many files as zip wihtout a file selection; can be combined with 'hidelist', so only a download button will be displayed (values: '1')

h2. Example

*simple*

@<txp:yab_download files="1,2,3,4" />@

*full*

@<txp:yab_download files="1,2,3,4" label1="Choose your download" label2="Accept Download at your own risk" label3="Klick here for download" label4="Yeah give me all as zip" notice="You have to accept the condition below" size="MB" formid="form-no-1" confirm="1" cats="category1, category2" exclude="5,7" sort="filename desc" zip="1" zipname="archive.zip" />@

The full example above will show you a form with two checkboxes. First one offers an option to download all the files zipped. The second checkbox is a confirmation checkbox.
The offered files will be the files with the file id #1, #2, #3, #4 and all files of the categories category1 and category2. If in category1 or category2 a file with the file id #5 and #7 so these will be excluded from download. The zipped file will be named as archive.zip.

h2. Note on zip creation

If you are running a php version 5.2.0 or above so the attribute @zip="1"@ will try to create the zip on harddisk instead in memory. If you have php 5.2.0 or above and you have problems with some restrictions like safe_mode, open_basedir etc., so try the attribute @zip="2"@. This will force the zip creation in memory.
# --- END PLUGIN HELP ---
-->
<?php
}
# --- BEGIN PLUGIN CODE ---
/**
 *
 * This plugin is released under the GNU General Public License Version 2 and above
 * Version 2: http://www.gnu.org/licenses/gpl-2.0.html
 * Version 3: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (class_exists('\Textpattern\Tag\Registry'))
{
	Txp::get('\Textpattern\Tag\Registry')
		->register('yab_download');
}

/**
 *
 * Displays the download form
 */
function yab_download($atts)
{
	extract(lAtts
		(array(
			'label1'		=> 'Files',
			'label2'		=> 'Please accept this condition!',
			'label3'		=> 'Download',
			'label4'		=> 'Download all as ZIP instead',
			'files'	 		=> '',
			'size'			=> '',
			'notice'		=> 'You have to accept the condition below for download.',
			'formid'		=> 'yab_cd_download',
			'confirm' 	=> '',
			'cats'			=> '',
			'exclude' 	=> '',
			'sort'			=> 'description desc',
			'zip'				=> '',
			'zipname'		=> 'all.zip',
			'hidelist'	=> '',
			'hidezip'		=> ''
			),$atts
		)
	);

	$select = '';
	$out = '';
	$notice_out = '';
	$filesizes = '';

	global $thisarticle;

	$url_title = $thisarticle['url_title'];
	$section = $thisarticle['section'];
	$posted = $thisarticle['posted'];
	$id = $thisarticle['thisid'];

	$purl = permlinkurl(array(
		'title'		 => '',
		'url_title' => $url_title,
		'section'	 => $section,
		'posted'		=> $posted,
		'thisid'		=> $id
		));

	// create empty array for zip
	if ($zip != '')
	{
		$z_filenames_array = array();
	}

	// look for given files in attribute @files@ and create option fields
	if ($files !== '')
	{
		$files_array = explode(',',$files);
		foreach ($files_array as $file)
		{
			$file = trim($file);
			$thisfile = fileDownloadFetchInfo('id = '.intval($file));
			if ($size != '')
			{
				$filesizes = ' ['.yab_cd_size($thisfile['size'], $size).']';
			}
			$select .= '<option value="'.$file.'">'.yab_cd_description($file).$filesizes.'</option>'.n;

			// fill array with filnames (file) for zip
			$z_filenames_array[] = $thisfile['filename'];
		}
	}

	// if attribute @cats@ is given create additional option fields with files from @cats@
	if ($cats != '')
	{
		$catsfiles = yab_cd_catfiles($cats, $sort, $size, $exclude);
		foreach ($catsfiles as $catsfile => $fileinfo)
		{
			if ($size != '')
			{
				$filesizes = ' ['.$fileinfo['size'].']';
			}
			$select .= '<option value="'.$catsfile.'">'.yab_cd_description($catsfile).$filesizes.'</option>'.n;

			// fill array with filnames (cat) for zip
			$thisfile = fileDownloadFetchInfo('id = '.intval($catsfile));
			$z_filenames_array[] = $thisfile['filename'];
		}
	}

	// is form is submitted and confirmation checkbox needed
	if (ps('yab_cd_submit') != '' and $confirm != '')
	{
		// if conf checkbox and no zip download
		if (ps('check') and ps('check2') == '')
		{
			$did = yab_cd_sanitize($_POST['files']);
			$durl = file_download_link(array('id' => $did),'');

			$out .= header('Content-Type: application/octet-stream');
			$out .= header('Content-Transfer-Encoding: binary');
			$out .= header('Location: '.$durl);
		}
		// if conf checkbox and zip download
		elseif (ps('check') and ps('check2') != '')
		{
			yab_zip_files($z_filenames_array, $zipname, $zip);
		}
		// no confirmation checkbox throw messge
		else
		{
			$notice_out .= graf($notice, ' class="notice"').n;
		}
	}
	// is form submitted and no confirmation checkbox needed
	elseif (ps('yab_cd_submit') != '' and $confirm == '')
	{
		// zip download
		if (ps('check2') != '')
		{
			yab_zip_files($z_filenames_array, $zipname, $zip);
		}
		// single file download
		else
		{
			$did = yab_cd_sanitize(ps('files'));
			$durl = file_download_link(array('id' => $did));

			$out .= header('Content-Type: application/octet-stream');
			$out .= header('Content-Transfer-Encoding: binary');
			$out .= header('Location: '.$durl);
		}
	}

	// create confirmation checkbox
	$c_checkbox = '';
	if ($confirm != '')
	{
		$c_checkbox .= '<p class="yab_cd_checkbox"><input type="checkbox" value="1" name="check" id="'.$formid.'-check" /><label for="'.$formid.'-check">'.$label2.'</label></p>'.n;
	}

	// create zip checkbox
	$zipcats_checkbox = '';
	if ($zip != '')
	{
		// hide or show zip checkbox
		if ($hidezip == '')
		{
			$zipcats_checkbox .= '<p class="yab_cd_zipcats"><input type="checkbox" value="1" name="check2" id="'.$formid.'-check2" /><label for="'.$formid.'-check2">'.$label4.'</label></p>'.n;
		}
		else
		{
			$zipcats_checkbox .= '<input type="hidden" value="1" name="check2" id="'.$formid.'-check2" />'.n;
		}
	}

	// hide or show files select list
	if ($hidelist == '')
	{
		$option_list = '<p class="yab_cd_select">'.n;
		$option_list .= '<label for="'.$formid.'-files">'.$label1.'</label><select name="files" id="'.$formid.'-files">'.n;
		$option_list .= $select;
		$option_list .= '</select>'.n;
		$option_list .= '</p>'.n;
	}
	else
	{
		if  (!isset($file))
		{
			$file = '';
		}
		$option_list = '<input type="hidden" name="files" id="'.$formid.'-files" value="'.$file.'" />'.n;
	}

	// create download form
	$out .= '<form method="post" action="'.$purl.'#'.$formid.'" id="'.$formid.'">'.n;
	$out .= '<div>'.n;
	$out .= $notice_out;
	$out .= $option_list;
	$out .= $zipcats_checkbox;
	$out .= $c_checkbox;
	$out .= '<input type="submit" value="'.$label3.'" name="yab_cd_submit" class="yab_cd_submit" />'.n;
	$out .= '</div>'.n;
	$out .= '</form>'.n;

	return $out;
}

/**
 *
 * Creates a zipfile vom given filenames array and force to download it.
 *
 * @param array $z_filenames_array Array of filenames with file id as $val
 * @param string $zipname Name of the created zipfile, given from attribute @zipname@
 * @param string $zip zip-modus ('2' to force creation in memory)
 * @return Force download of the zipfile with header modifications
 */
function yab_zip_files($z_filenames_array, $zipname, $zip)
{
	global $prefs;
	$path = $prefs['file_base_path'];
	$tmpdir = $prefs['tempdir'];

	// test for zlib (php5.2); create zip on disk
	if (version_compare(phpversion(), '5.2.0', '>=') and class_exists('ZipArchive') and $zip != '2')
	{
		// start new zipfile instance
		$tmpfile = tempnam($tmpdir,'zip');
		$zipfile = new ZipArchive();

		if ($zipfile->open($tmpfile, constant('ZipArchive::OVERWRITE')) === true)
		{
			// add every file from array to zipfile
			foreach ($z_filenames_array as $key => $val)
			{
				$z_filename = $path.'/'.$val;
				$zipfile->addFile($z_filename,$val);
			}
			$zipfile->close();
			@ob_end_clean();
			// create headers to force download
			header('Content-Type: application/zip');
			header('Content-Length: '.filesize($tmpfile));
			header('Content-Disposition: attachment; filename="'.$zipname.'"');
			// delete zipfile
			readfile($tmpfile);
			@unlink($tmpfile);
			exit;
		}
	}
	// create zip in memory
	else
	{
		// start new zipfile instance
		$zipfile = new zipfile();

		// add every file from array to zipfile
		foreach ($z_filenames_array as $key => $val)
		{
			$z_filename = $path.'/'.$val;
			$handle = fopen($z_filename, "r");
			$content = fread($handle, filesize($z_filename));
			fclose ($handle);

			$zipfile->addFile($content, $val);
		}
		$dump_buffer = $zipfile->file();
		// create headers to force download
		header('Content-Type: application/zip');
		header('Content-Length: '.strlen($dump_buffer));
		header('Content-Disposition: attachment; filename="'.$zipname.'"');
		echo $dump_buffer;
		exit;
	}
}

/**
 *
 * Creates an array of fileinfo from files assigned with specific categories
 *
 * @param string $category Categories from attribute @cats@
 * @param string $sort Sort from attribute @sort@
 * @param string $format Unit of the filesize, attribute @size@
 * @param string $exclude Files to exclude from attribute @exclude@
 * @return array
 */
function yab_cd_catfiles($category = '', $sort = '', $format = '', $exclude = '')
{
	// create SQL-query
	$where = array('1=1');
	$where[] = "category IN ('".join("','", doSlash(do_list($category)))."')";
	if ($exclude != '')
	{
		$where[] = "id NOT IN ('".join("','", doSlash(do_list($exclude)))."')";
	}

	$qparts = array('order by '.doSlash($sort));

	// and query
	$rs = safe_rows_start('*', 'txp_file', join(' and ', $where).' '.join(' ', $qparts));

	if ($rs)
	{
		$out = array();

		// every row in db
		while ($a = nextRow($rs))
		{
			$id = $a['id'];
			$filesize = $a['size'];

			$out[$id]['link'] = file_download_link(array('id' => $id),'');
			$out[$id]['size'] = yab_cd_size($filesize, $format);
			$out[$id]['description'] = yab_cd_description($id);
		}
	}

	return $out;
}


/**
 *
 * Calculated the filesize depending on given unit
 *
 * @param string $filesize Given filesize in Byte
 * @param string $format Unit of the calculated filesize
 * @return string Ready for output
 */
function yab_cd_size($filesize, $format = '')
{
	$decimals = 2;
	$size = $filesize;

	if (!in_array($format, array('B','KB','MB','GB','PB')))
	{
		$divs = 0;
		while ($size >= 1024)
		{
			$size /= 1024;
			$divs++;
		}

		switch ($divs)
		{
			case 1:
				$format = 'KB';
				break;
			case 2:
				$format = 'MB';
				break;
			case 3:
				$format = 'GB';
				break;
			case 4:
				$format = 'PB';
				break;
			case 0:
				default:
				$format = 'B';
				break;
		}
	}

	switch ($format)
	{
		case 'KB':
			$size /= 1024;
			break;
		case 'MB':
			$size /= (1024*1024);
			break;
		case 'GB':
			$size /= (1024*1024*1024);
			break;
		case 'PB':
			$size /= (1024*1024*1024*1024);
			break;
		case 'B':
			default:
			break;
	}

	return number_format($size, $decimals).$format;
}

/**
 *
 * Outputs filename or given filedescription if exists.
 *
 * @param string $fileid ID of the file
 * @return string Ready for output
 */
function yab_cd_description($fileid)
{
	$thisfile = fileDownloadFetchInfo('id = '.intval($fileid));

	if ($thisfile)
	{
		if ($thisfile['description'])
		{
			$out = htmlspecialchars($thisfile['description']);
		}
		else
		{
			$out = htmlspecialchars($thisfile['filename']);
		}
	}
	else
	{
		$out = 'No file with an ID #'.$fileid.' available';
	}
	return $out;
}

/**
 *
 * Small sanitization. Strips all non-numerics.
 *
 * @param string $in POSTed data
 * @return string
 */
function yab_cd_sanitize($in)
{
	$out = preg_replace('/[^0-9]/', '', $in);

	return $out;
}

/**
 *
 * @version $Id: zip.lib.php 10240 2007-04-01 11:02:46Z cybot_tm $
 *
 *
 * Zip file creation class.
 * Makes zip files.
 *
 * Based on :
 *
 *	http://www.zend.com/codex.php?id=535&single=1
 *	By Eric Mueller <eric@themepark.com>
 *
 *	http://www.zend.com/codex.php?id=470&single=1
 *	by Denis125 <webmaster@atlant.ru>
 *
 *	a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified
 *	date and time of the compressed file
 *
 * Official ZIP file format: http://www.pkware.com/appnote.txt
 *
 * @access	public
 */
class zipfile
{
		/**
		 * Array to store compressed data
		 *
		 * @var	array		$datasec
		 */
		var $datasec			= array();

		/**
		 * Central directory
		 *
		 * @var	array		$ctrl_dir
		 */
		var $ctrl_dir		 = array();

		/**
		 * End of central directory record
		 *
		 * @var	string	 $eof_ctrl_dir
		 */
		var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

		/**
		 * Last offset position
		 *
		 * @var	integer	$old_offset
		 */
		var $old_offset	 = 0;


		/**
		 * Converts an Unix timestamp to a four byte DOS date and time format (date
		 * in high two bytes, time in low two bytes allowing magnitude comparison).
		 *
		 * @param	integer	the current Unix timestamp
		 *
		 * @return integer	the current date in a four byte DOS format
		 *
		 * @access private
		 */
		function unix2DosTime($unixtime = 0) {
				$timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

				if ($timearray['year'] < 1980) {
						$timearray['year']		= 1980;
						$timearray['mon']		 = 1;
						$timearray['mday']		= 1;
						$timearray['hours']	 = 0;
						$timearray['minutes'] = 0;
						$timearray['seconds'] = 0;
				} // end if

				return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
								($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
		} // end of the 'unix2DosTime()' method


		/**
		 * Adds "file" to archive
		 *
		 * @param	string	 file contents
		 * @param	string	 name of the file in the archive (may contains the path)
		 * @param	integer	the current timestamp
		 *
		 * @access public
		 */
		function addFile($data, $name, $time = 0)
		{
				$name		 = str_replace('\\', '/', $name);

				$dtime		= dechex($this->unix2DosTime($time));
				$hexdtime = '\x' . $dtime[6] . $dtime[7]
									. '\x' . $dtime[4] . $dtime[5]
									. '\x' . $dtime[2] . $dtime[3]
									. '\x' . $dtime[0] . $dtime[1];
				eval('$hexdtime = "' . $hexdtime . '";');

				$fr	 = "\x50\x4b\x03\x04";
				$fr	 .= "\x14\x00";						// ver needed to extract
				$fr	 .= "\x00\x00";						// gen purpose bit flag
				$fr	 .= "\x08\x00";						// compression method
				$fr	 .= $hexdtime;						 // last mod time and date

				// "local file header" segment
				$unc_len = strlen($data);
				$crc		 = crc32($data);
				$zdata	 = gzcompress($data);
				$zdata	 = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
				$c_len	 = strlen($zdata);
				$fr			.= pack('V', $crc);						 // crc32
				$fr			.= pack('V', $c_len);					 // compressed filesize
				$fr			.= pack('V', $unc_len);				 // uncompressed filesize
				$fr			.= pack('v', strlen($name));		// length of filename
				$fr			.= pack('v', 0);								// extra field length
				$fr			.= $name;

				// "file data" segment
				$fr .= $zdata;

				// "data descriptor" segment (optional but necessary if archive is not
				// served as file)
				// nijel(2004-10-19): this seems not to be needed at all and causes
				// problems in some cases (bug #1037737)
				//$fr .= pack('V', $crc);								 // crc32
				//$fr .= pack('V', $c_len);							 // compressed filesize
				//$fr .= pack('V', $unc_len);						 // uncompressed filesize

				// add this entry to array
				$this -> datasec[] = $fr;

				// now add to central directory record
				$cdrec = "\x50\x4b\x01\x02";
				$cdrec .= "\x00\x00";								// version made by
				$cdrec .= "\x14\x00";								// version needed to extract
				$cdrec .= "\x00\x00";								// gen purpose bit flag
				$cdrec .= "\x08\x00";								// compression method
				$cdrec .= $hexdtime;								 // last mod time & date
				$cdrec .= pack('V', $crc);					 // crc32
				$cdrec .= pack('V', $c_len);				 // compressed filesize
				$cdrec .= pack('V', $unc_len);			 // uncompressed filesize
				$cdrec .= pack('v', strlen($name)); // length of filename
				$cdrec .= pack('v', 0);						 // extra field length
				$cdrec .= pack('v', 0);						 // file comment length
				$cdrec .= pack('v', 0);						 // disk number start
				$cdrec .= pack('v', 0);						 // internal file attributes
				$cdrec .= pack('V', 32);						// external file attributes - 'archive' bit set

				$cdrec .= pack('V', $this -> old_offset); // relative offset of local header
				$this -> old_offset += strlen($fr);

				$cdrec .= $name;

				// optional extra field, file comment goes here
				// save to central directory
				$this -> ctrl_dir[] = $cdrec;
		} // end of the 'addFile()' method


		/**
		 * Dumps out file
		 *
		 * @return	string	the zipped file
		 *
		 * @access public
		 */
		function file()
		{
				$data		= implode('', $this -> datasec);
				$ctrldir = implode('', $this -> ctrl_dir);

				return
						$data .
						$ctrldir .
						$this -> eof_ctrl_dir .
						pack('v', sizeof($this -> ctrl_dir)) .	// total # of entries "on this disk"
						pack('v', sizeof($this -> ctrl_dir)) .	// total # of entries overall
						pack('V', strlen($ctrldir)) .					 // size of central dir
						pack('V', strlen($data)) .							// offset to start of central dir
						"\x00\x00";														 // .zip file comment length
		} // end of the 'file()' method

} // end of the 'zipfile' class
# --- END PLUGIN CODE ---
?>
