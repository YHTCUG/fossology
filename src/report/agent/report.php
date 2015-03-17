<?php
/*
 Author: Daniele Fognini, Shaheem Azmal, Anupam Ghosh
 Copyright (C) 2015, Siemens AG

 This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License version 2 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

		

define("REPORT_AGENT_NAME", "report");

use Fossology\Lib\Agent\Agent;
use Fossology\Lib\Dao\UploadDao;
use Fossology\Lib\Report\LicenseClearedGetter;
use Fossology\Lib\Report\XpClearedGetter;
use \PhpOffice\PhpWord ;

include_once(__DIR__ . "/version.php");

class ReportAgent extends Agent
{

  /** @var LicenseClearedGetter  */
  private $licenseClearedGetter;

  /** @var XpClearedGetter */
  private $XpClearedGetter;
  
  /** @var cpClearedGetter */
  private $cpClearedGetter;

  /** @var ipClearedGetter */
  private $ipClearedGetter;

  /** @var eccClearedGetter */
  private $eccClearedGetter;
  
  /** @var UploadDao */
  private $uploadDao;

  function __construct()
  {
    $this->cpClearedGetter = new XpClearedGetter("copyright", "statement", false, "content ilike 'Copyright%'");
    $this->ipClearedGetter = new XpClearedGetter("ip", "ip", false);
    $this->eccClearedGetter = new XpClearedGetter("ecc", "ecc", false);
    $this->licenseClearedGetter = new LicenseClearedGetter();

    parent::__construct(REPORT_AGENT_NAME, AGENT_VERSION, AGENT_REV);

    $this->uploadDao = $this->container->get("dao.upload");
  }

  function processUploadId($uploadId)
  {
    $groupId = $this->groupId;

    $this->heartbeat(0);
    $licenses = $this->licenseClearedGetter->getCleared($uploadId, $groupId);
    $this->heartbeat(count($licenses["statements"]));
    $copyrights = $this->cpClearedGetter->getCleared($uploadId, $groupId);
    $this->heartbeat(count($copyrights["statements"]));
    $ecc = $this->eccClearedGetter->getCleared($uploadId, $groupId);
    $this->heartbeat(count($ecc["statements"]));
    $ip = $this->ipClearedGetter->getCleared($uploadId, $groupId);
    $this->heartbeat(count($ip["statements"]));
    $contents = array("licenses" => $licenses,
                                    "copyrights" => $copyrights,
                                    "ecc" => $ecc,
			            "ip" => $ip
    );

    $this->writeReport($contents, $uploadId);

    return true;
  }

//header
  private function headerGeneration($section)
  {
    $header = $section->createHeader();
    $header->addText(htmlspecialchars("SIEMENS" ), array("name" => "Tahoma", "size" => 10));
  }

  //footer
  private function footerGeneration($section)
  {
    $footer = $section->createFooter();
    $footer->addText(array("underline"=> single));
    $footer->addPreserveText(htmlspecialchars("Copyright © 2014 Siemens AG - Restricted         Template version: 31.10.2013        Page {PAGE} of {NUMPAGES}." ), array("size" => 10 , "bold" => true));
  }

  //heading 
  private function headingOne($section)
  {
    $headerStyle = array("name" => Arial, "size" => 22, "bold" => true, "underline" => single);
			 
    $heading1= "License Clearing Report – V1";
    $section->addText(htmlspecialchars($heading1), $headerStyle);
  }

//report generation
  private function bodyClearingReport($contents,$uploadId,$section)
  {
    $message = $this->generateReport($contents, $packageName);
    $section->addText(htmlspecialchars($message ));
  }
        
  private function summeryTable($section)
  {
    
    $paragraphStyle = array("spaceAfter" => 2, "spaceBefore" => 2,"spacing" => 2);          
    $cellRowContinue = array("vMerge" => "continue");
    $firstRowStyle = array("name" => Arial, "size" => 14, "bold" => true);
    $firstRowStyle1 = array("name" => Arial, "size" => 12, "bold" => true);
    $firstRowStyle2 = array("name" => Arial, "size" => 12, "bold" => false);
    $checkBoxStyle = array("name" => Arial,"size" => 10); 								         
		
    $cellRowSpan = array("vMerge" => "restart", "valign" => "top");
    $cellColSpan = array("gridSpan" => 3, "valign" => "center");

    $rowWidth = 200;
    $cellFirstLen = 2000;	 
    $cellSecondLen = 4000;	 
    $cellThirdLen = 6500;	 

    $table = $section->addTable("Report Table");
    
    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellColSpan)->addText(htmlspecialchars(" Clearing report for OSS component"),$firstRowStyle,$paragraphStyle);
		 
    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowSpan)->addText(htmlspecialchars(" Clearing Information"),$firstRowStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Department"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(" IC SG EA SOL/PRO"),$firstRowStyle2,$paragraphStyle);
		 
    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue)->addText(null,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Type"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(" OSS clearing only"),$firstRowStyle2,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Prepared by"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(" <date> <last name, first name> <department>"),$firstRowStyle2,$paragraphStyle);
      
    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Reviewed by (opt.)"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(" <date> <last name, first name> <department>"),$firstRowStyle2,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Released by"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(" IC SG EA SOL/PRO"),$firstRowStyle2,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Clearing Status"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen);
    $cell->addCheckBox(" chkBox1", htmlspecialchars(" in progress"),$checkBoxStyle,$paragraphStyle);
    $cell->addCheckBox(" chkBox2", htmlspecialchars(" release"),$checkBoxStyle,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowSpan)->addText(htmlspecialchars(" Component Information"),$firstRowStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Community"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(" <URL>"),$firstRowStyle2,$paragraphStyle);
		 
    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Component"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(""),$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Version"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(""),$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Source URL"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(""),$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Release date"),$firstRowStyle1,$paragraphStyle);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(""),$paragraphStyle);

    $table->addRow($rowWidth);
    $cell = $table->addCell($cellFirstLen,$cellRowContinue);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars(" Main license(s)"),$firstRowStyle1);
    $cell = $table->addCell($cellThirdLen)->addText(htmlspecialchars(" <list here the name(s) of the global license(s)>"),$firstRowStyle2);

    $section->addTextBreak();
  }

  private function clearingProtocolChangeLogTable($section)
  {
    $headerStyle = array("name" => Arial,"size" => 18,"bold" => true);
    $firstRowStyle = array("bgColor" => "C0C0C0");
    $firstRowTextStyle = array("name" => Arial, "size" => 12, "bold" => true);

    $heading = "1. Clearing Protocol Change Log";

    $section->addText(htmlspecialchars($heading), $headerStyle);

    $rowWidth = 600;
    $rowWidth1 = 200;
    $cellFirstLen = 2000;	 
    $cellSecondLen = 4000;	 
    $cellThirdLen = 10000;	 
    $table = $section->addTable("Report Table");

    $table->addRow($rowWidth);
    $cell = $table->addCell($cellFirstLen,$firstRowStyle)->addText(htmlspecialchars("Last Update"),$firstRowTextStyle);
    $cell = $table->addCell($cellSecondLen,$firstRowStyle)->addText(htmlspecialchars("Responsible"),$firstRowTextStyle);
    $cell = $table->addCell($cellThirdLen,$firstRowStyle)->addText(htmlspecialchars("Comments"),$firstRowTextStyle);

    $table->addRow($rowWidth1);
    $cell = $table->addCell($cellFirstLen);
    $cell = $table->addCell($cellSecondLen);
    $cell = $table->addCell($cellThirdLen);
		
    $section->addTextBreak();
  }


  private function functionalityTable($section)
  {
   $headerStyle = array("name" => Arial,"size" => 18,"bold" => true);
   $infoTextStyle = array("name" => Arial,"size" => 11, "color" => "0000FF");
		
   $heading1 = "2. Functionality";
   $infoText = "<Hint: look in ohloh.net in the mainline portal or Component database or on the communities web page for information>";
			
   $section->addText(htmlspecialchars($heading1), $headerStyle);
   $section->addText(htmlspecialchars($infoText),$infoTextStyle);
		
   $section->addTextBreak();
  }


  private function assessmentSummeryTable($section)
  { 
    $paragraphStyle = array("spaceAfter" => 0, "spaceBefore" => 0,"spacing" => 0);          
    $heading = "3. Assessment Summary:";
    $infoText = "The following table only contains significant obligations, restrictions & risks for a quick overview – all obligations, restrictions & risks according to Section 3 must be considered.";
      
    $headerStyle = array("name" => Arial,"size" => 18,"bold" => true);
    $infoTextStyle = array("name" => Arial,"size" => 10, "color" => "000000");
    $leftColStyle = array("name" => Arial,"size" => 11, "color" => "000000","bold" =>true);
    $rightColStyleBlue = array("name" => Arial,"size" => 11, "color" => "0000A0","italic"=>true);
    $rightColStyleBlack = array("name" => Arial,"size" => 11, "color" => "000000");
    $rightColStyleBlackWithItalic = array("name" => Arial,"size" => 11, "color" => "000000","italic"=>true);

    $rowWidth = 200;
    $cellFirstLen = 5000;
    $cellSecondLen = 11000;

    $section->addText(htmlspecialchars($heading), $headerStyle);
    $section->addText(htmlspecialchars($infoText), $infoTextStyle);

    $table = $section->addTable("Report Table");

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars("General assessment"),$leftColStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars("<e.g. strong copyleft effect, license incompatibilities,  or also “easy to fulfill obligations, common rules only”>"),$rightColStyleBlue,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars("Mainline Portal Status for component"),$leftColStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen);
    $cell->addCheckBox("chkBox1", htmlspecialchars("Mainline"),$rightColStyleBlack,$paragraphStyle);
    $cell->addCheckBox("chkBox2", htmlspecialchars("Specific"),$rightColStyleBlack,$paragraphStyle);
    $cell->addCheckBox("chkBox3", htmlspecialchars("Denied"),$rightColStyleBlack,$paragraphStyle);
 
    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars("License Incompatibility found"),$leftColStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen);
    $cell->addCheckBox("chkBox1", htmlspecialchars("no"),$rightColStyleBlackWithItalic,$paragraphStyle);
    $cell->addCheckBox("chkBox2", htmlspecialchars("yes"),$rightColStyleBlackWithItalic,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars("Source / binary integration notes"),$leftColStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen);
    $cell->addCheckBox("chkBox1", htmlspecialchars("no critical files found, source code and binaries can be used as is"),$rightColStyleBlackWithItalic,$paragraphStyle);
    $cell->addCheckBox("chkBox2", htmlspecialchars("critical files found, source code needs to be adapted and binaries possibly re-built"),$rightColStyleBlackWithItalic,$paragraphStyle);
    $cell->addText(htmlspecialchars("<if there are critical files found, please provide some additional information or refer to chapter(s) in this documents where additional information is given>"),$rightColStyleBlue,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars("Dependency notes"),$leftColStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen);
    $cell->addCheckBox("chkBox1", htmlspecialchars("no dependencies found, neither in source code nor in binaries"),$rightColStyleBlackWithItalic,$paragraphStyle);
    $cell->addCheckBox("chkBox2", htmlspecialchars("dependencies found in source code"),$rightColStyleBlackWithItalic,$paragraphStyle);
    $cell->addCheckBox("chkBox3", htmlspecialchars("dependencies found in binaries"),$rightColStyleBlackWithItalic,$paragraphStyle);
    $cell->addText(htmlspecialchars("<if there are dependencies found, please provide some additional information or refer to chapter(s) in this documents where additional information is given>"),$rightColStyleBlue,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars("Additional notes"),$leftColStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars("<e.g. only global license was cleared since the project who requested the clearing only uses the component without mixing it with Siemens code>"),$rightColStyleBlue,$paragraphStyle);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($cellFirstLen)->addText(htmlspecialchars("General Risks (optional)"),$leftColStyle,$paragraphStyle);
    $cell = $table->addCell($cellSecondLen)->addText(htmlspecialchars("<e.g. not maintained by community anymore – must be supported by Siemens – see ohloh.net for info>"),$rightColStyleBlue,$paragraphStyle);
		
    $section->addTextBreak();
  }


  private function todoTable($section)
  {   	  
    $paragraphStyle = array("spaceAfter" => 0, "spaceBefore" => 0,"spacing" => 0);          
    $headerStyle = array("name" => Arial,"size" => 18,"bold" => true);
    $rowStyle = array("bgColor" => "C0C0C0","spaceBefore" => 0,"spaceAfter" => 0,"spacing" => 0);
    $rowTextStyleLeft = array("name" => Arial, "size" => 10, "bold" => true);
    $rowTextStyleRight = array("name" => Arial, "size" => 10, "bold" => false);
    $rowTextStyleRightBold = array("name" => Arial, "size" => 10, "bold" => true);
    $subHeadingStyle = array(name => Arial, size => 14, "italic"=>true);   
    $heading = "4. When using this component, you need to fulfill the following “ToDos”";
    $subHeading = " 4.1. Common obligations, restrictions and risks:";
    $subHeadingInfoText = "  There is a list of common rules which was defined to simplify the To-Dos for development and distribution. The following list contains rules for development, and      distribution which must always be followed!";
    $rowWidth = 5;
    $firstColLen = 500;
    $secondColLen = 15500;  	 	
    	  
    $section->addText(htmlspecialchars($heading), $headerStyle);
    $section->addText(htmlspecialchars($subHeading), $subHeadingStyle);
    $section->addText(htmlspecialchars($subHeadingInfoText),$rowTextStyleRight);

    $r1c1 = "1";
    $r2c1 = "1.a";
    $r3c1 = "1.b";
    $r4c1 = "1.c";
    $r5c1 = "2";
    $r6c1 = "2.a";
    $r7c1 = "2.b";
    $r8c1 = "3";
    $r9c1 = "3.a";
    $r10c1 = "3.b";
    $r11c1 = "3.c";
    $r12c1 = "4";
    $r13c1 = "4.a";

    $r1c2 = "Documentation of license conditions and copyright notices in product documentation (ReadMe_OSS)";
    $r2c21 = "All relevant licenses (global and others - see below) must be added to the legal approved Readme_OSS template.";
    $r2c22 = "Remark:";
    $r2c23 = "“Do Not Use” licenses must not be added to the ReadMe_OSS";
    $r3c2 = "Add all copyrights to README_OSS";
    $r4c2 = "Add all relevant acknowledgements to Readme_OSS";
    $r5c21 = "Modifications in Source Code";
    $r5c22 = "If modifications are permitted:";
    $r6c2 = "Do not change or delete Copyright, patent, trademark, attribution notices or any further legal notices or license texts in any files - i.e. neither within any source file of the component package nor in any of its documentation files.";
    $r7c21 = "Document all changes and modifications in source code files with copyright notices:";
    $r7c22 = "Add copyright (including company and date), function, reason for modification in the header.";
    $r7c23 = "Example:";
    $r7c24 = "© Siemens AG, 2013";
    $r7c25 = "March 18th, 2013 Modified helloworld() – fix memory leak";
    $r8c2 = "Obligations and risk assessment regarding distribution";
    $r9c2 = "Ensure that your distribution terms which are agreed with Siemens’ customers (e.g. standard terms, “AGB”, or individual agreements) define that the open source license conditions shall prevail over the Siemens’ license conditions with respect to the open source software (usually this is part of Readme OSS).";
    $r10c2 = "Do not use any names, trademarks, service marks or product names of the author(s) and/or licensors to endorse or promote products derived from this software component without the prior written consent of the author(s) and/or the owner of such rights.";
    $r11c2 = "Consider for your product/project if you accept the general risk that";
    $r11c21 = "• it cannot be verified whether contributors to open source software are legally permitted to contribute (the code could e.g. belong to his employer, and not the developer). Usually, disclaimers or contribution policies exclude responsibility for contributors even to verify the legal status.	";
    $r11c22 = "•  there is no warranty or liability from the community – i.e. error corrections must be made by Siemens, and Siemens must cover all damages";
    $r12c2 = "IC SG EA specific rules";
    $r13c21 = "The following statement must be added to any manual. The language of the statement is equal to the manual language. Check translation with documentation department.";
    $r13c22 = "English:";
    $r13c23 = "	The product contains, among other things, Open Source Software developed by third parties. The Open Source Software used in the product and the license agreements concerning this software can be found in the Readme_OSS. These Open Source Software files are protected by copyright. Your compliance with those license conditions will entitle you to use the Open Source Software as foreseen in the relevant license. In the event of conflicts between Siemens license conditions and the Open Source Software license conditions, the Open Source Software conditions shall prevail with respect to the Open Source Software portions of the software. The Open Source Software is licensed royalty-free. Insofar as the applicable Open Source Software License Conditions provide for it you can order the source code of the Open Source Software from your Siemens sales contact - against payment of the shipping and handling charges - for a period of at least 3 years since purchase of the Product. We are liable for the Product including the Open Source Software contained in it pursuant to the license conditions applicable to the Product. Any liability for the Open Source Software beyond the program flow intended for the Product is explicitly excluded. Furthermore any liability for defects resulting from modifications to the Open Source Software by you or third parties is excluded. We do not provide any technical support for the Product if it has been modified.";
		
   $table = $section->addTable("Report Table");
    
   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen,$rowStyle)->addText(htmlspecialchars("1"),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen,$rowStyle)->addText(htmlspecialchars("Documentation of license conditions and copyright notices in product documentation (ReadMe_OSS)"),$rowTextStyleRightBold,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("1.a"),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen);
   $cell->addText(htmlspecialchars("All relevant licenses (global and others - see below) must be added to the legal approved Readme_OSS template."),$rowTextStyleRight,$paragraphStyle);
   $cell->addText(htmlspecialchars("Remark:"),$rowTextStyleRightBold,$paragraphStyle);
   $cell->addText(htmlspecialchars("“Do Not Use” licenses must not be added to the ReadMe_OSS"),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("1.b"),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("Add all copyrights to README_OSS"),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("1.c"),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("Add all relevant acknowledgements to Readme_OSS"),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen,$rowStyle)->addText(htmlspecialchars("2"),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen,$rowStyle);
   $cell->addText(htmlspecialchars("Modifications in Source Code"),$rowTextStyleRightBold,$paragraphStyle);
   $cell->addText(htmlspecialchars("If modifications are permitted:"),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("2.a"),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("Do not change or delete Copyright, patent, trademark, attribution notices or any further legal notices or license texts in any files - i.e. neither within any source file of the component package nor in any of its documentation files."),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r7c1),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen);
   $cell->addText(htmlspecialchars($r7c21),$rowTextStyleRight,$paragraphStyle);
   $cell->addText(htmlspecialchars($r7c22),$rowTextStyleRight,$paragraphStyle);
   $cell->addText(htmlspecialchars($r7c23),$rowTextStyleRight,$paragraphStyle);
   $cell->addText(htmlspecialchars($r7c24),$rowTextStyleRight,$paragraphStyle);
   $cell->addText(htmlspecialchars($r7c25),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen,$rowStyle)->addText(htmlspecialchars($r8c1),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen,$rowStyle)->addText(htmlspecialchars($r8c2),$rowTextStyleRightBold,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r9c1),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen)->addText(htmlspecialchars($r9c2),$rowTextStyleRight,$paragraphStyle);


   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r10c1),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen)->addText(htmlspecialchars($r10c2),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r11c1),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen);
   $cell->addText(htmlspecialchars($r11c2),$rowTextStyleRightBold,$paragraphStyle);
   $cell->addText(htmlspecialchars($r11c21),$rowTextStyleRight,$paragraphStyle);
   $cell->addText(htmlspecialchars($r11c22),$rowTextStyleRight,$paragraphStyle);

   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen,$rowStyle)->addText(htmlspecialchars($r12c1),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen,$rowStyle)->addText(htmlspecialchars($r12c2),$rowTextStyleRightBold,$paragraphStyle);
		
   $table->addRow($rowWidth,$paragraphStyle);
   $cell = $table->addCell($firstColLen)->addText(htmlspecialchars($r13c1),$rowTextStyleLeft,$paragraphStyle);
   $cell = $table->addCell($secondColLen);
   $cell->addText(htmlspecialchars($r13c21),$rowTextStyleRightBold,$paragraphStyle);
   $cell->addText(htmlspecialchars($r13c22),$rowTextStyleRight,$paragraphStyle);
   $cell->addText(htmlspecialchars($r13c23),$rowTextStyleRight,$paragraphStyle);

   $section->addTextBreak();
  }
	

  private function todoObliTable($section)
  {

    $firstRowStyle = array("bgColor" => "C0C0C0");
    $firstRowTextStyle = array("name" => Arial, "size" => 11, "bold" => true);
    $secondRowTextStyle1 = array("name" => Arial, "size" => 11, "bold" => false);
    $secondRowTextStyle2 = array("name" => Arial, "size" => 10, "bold" => false);
    $secondRowTextStyle2Bold = array("name" => Arial, "size" => 10, "bold" => true);
    $firstColStyle = array ("name" => Arial, "size" => 11 , "bold"=> true, "bgcolor"=> "FFFF00");
    $secondColStyle = array ("name" => Arial, "size" => 11 , "bold"=> true, "bgcolor"=> "00E1E1");
    $subHeadingStyle = array("name" => Arial, size => 14, "italic"=>true);
    $subHeading = " 4.2.  Additional obligations, restrictions & risks beyond common rules";
    $subHeadingInfoText1 = "  In this chapter you will find the summary of additional license conditions (relevant for development and distribution) for the OSS component.";
    $subHeadingInfoText2 = "  * The following information helps the project to determine the responsibility regarding the To Do’s. But it is not limited to Development or Distribution. ";
        	  
    $section->addText(htmlspecialchars($subHeading), $subHeadingStyle);
    $section->addText(htmlspecialchars($subHeadingInfoText1),$rowTextStyleRight);
    $section->addText(htmlspecialchars($subHeadingInfoText2),$rowTextStyleRight);

    $rowWidth = 200;
    $firstColLen = 2000;
    $secondColLen = 1500;  	 	
    $thirdColLen = 9500;
    $fourthColLen = 1500 ;
    $fifthColLen = 1500; 
   
    $table = $section->addTable("Report Table");
    
    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstRowStyle)->addText(htmlspecialchars("Obligation"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$firstRowStyle)->addText(htmlspecialchars("License"),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen,$firstRowStyle)->addText(htmlspecialchars("License section reference and short Description"),$firstRowTextStyle);
    $cell = $table->addCell($fourthColLen,$firstRowStyle)->addText(htmlspecialchars("Focus area for Development "),$firstRowTextStyle);
    $cell = $table->addCell($fifthColLen,$firstRowStyle)->addText(htmlspecialchars("Focus area for Distribution"),$firstRowTextStyle);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Additional binaries found"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars("-"),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("In this component additional binaries are found."),$secondRowTextStyle1);
    $cell->addText(htmlspecialchars("If you want to use the binaries distributed with the source/binaries ((where no corresponding source code is part of the distribution of this component) you must do a clearing also for those components (add them to the Mainline Portal). The license conditions of the additional binaries are NOT part of this clearing protocol."),$secondRowTextStyle1);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars(""));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars(""),$firstRowTextStyle);

    
    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Dual Licensing (optional obligation, check with license)"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("Add explicit note to Readme_OSS:"),$secondRowTextStyle2);
    $cell->addText(htmlspecialchars("To the extend files may be licensed under <license1> or <license2>, in this context <license1> has been chosen."),$secondRowTextStyle2);
    $cell->addText(htmlspecialchars("This shall not restrict the freedom of future contributors to choose either <license1> or <license2>.”"),$secondRowTextStyle2);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars(""));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars(""),$firstRowTextStyle);


    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Do not use the following Files"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell->addText(htmlspecialchars("<reason for that>"),$secondRowTextStyle2);
    $cell->addText(htmlspecialchars("Filelist:"),$secondRowTextStyle2Bold,$secondRowTextStyle2);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));


    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Copyleft Effect"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars(""),$secondRowTextStyle2Bold);


    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Restrictions for advertising materials"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars(""),$secondRowTextStyle2Bold);
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));


    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Additional Rules for modification"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars(""),$secondRowTextStyle2Bold);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Additional documentation requirements for modifications (e.g. notice file with author’s name)"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Include special acknowledgments in advertising material"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars(""),$secondRowTextStyle2Bold);
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstColStyle)->addText(htmlspecialchars("Specific Risks"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$secondColStyle)->addText(htmlspecialchars(""),$firstRowTextStyle);
    $cell = $table->addCell($thirdColLen);
    $cell = $table->addCell($fourthColLen)->addText(htmlspecialchars("X"),$secondRowTextStyle2Bold,array("align" => "center"));
    $cell = $table->addCell($fifthColLen)->addText(htmlspecialchars(""),$secondRowTextStyle2Bold);

    $section->addTextBreak();
  }


  private function todoObliList($section)
  {
    
    $firstRowStyle = array("bgColor" => "C0C0C0");
    $firstRowTextStyle = array("name" => Arial, "size" => 10, "bold" => true);
    
    $subHeadingStyle = array(name => Arial, size => 14, "italic"=>true);
    $subHeading = "4.3.	File list with specific obligations "; 

    $section->addText(htmlspecialchars($subHeading), $subHeadingStyle);
    
    $rowWidth = 500;
    $firstColLen = 4000;
    $secondColLen = 5000;  	 	

    $table = $section->addTable("Report Table");

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen,$firstRowStyle)->addText(htmlspecialchars("Issues obligations (licenses, patent …) see chapter 4.2"),$firstRowTextStyle);
    $cell = $table->addCell($secondColLen,$firstRowStyle)->addText(htmlspecialchars("Files (embedded document) (optional)"),$firstRowTextStyle);

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("Sleepycat License"));
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars(""));

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("Remove Files due to patent issues"));
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars(""));

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("GPL 2"));
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("<path-filenames>"));

    $table->addRow($rowWidth);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("Perl license: Dual license (Artistic or GPL)"));
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("<path-filelist>"));

    $section->addTextBreak();
  }    

  private function forOtherTodos($section)
  {
    $paragraphStyle = array("spaceAfter" => 0, "spaceBefore" => 0,"spacing" => 2);          
    $subHeadingStyle = array("name" => Arial, "size" => 14, "italic"=>true);
    $subSubHeadingStyle = array("name" => Arial, "size" => 16, "bold"=>true);
    $subSubHeadingInfoTextStyle = array("name" => Arial, "size" => 10, "bold"=>false);
    $subSubHeadingInfoTextStyle1 = array("name" => Arial, "size" => 10, "bold"=>true);

    $subHeading = "4.4.	Further general obligations, restrictions & risks"; 
    $subSubHeading = "   4.4.1	 Export Restrictions";
    $subSubHeadingInfoText = "Assess potential export restrictions together with your export control agent regarding this software component as defined in your applicable process.";
    $subSubHeadingInfoText1 = "Export Restrictions found in Source Code:";
    $subSubHeadingInfoText2 = "No export restriction notices found in source scan – export restriction clarification is responsibility of Siemens projects/product managers.";

    $subSubHeading1 = "   4.4.2   Security Vulnerabilities";
    $subSubHeadingInfoText3 = "Security Vulnerabilities must be examined in product specific use - project leader is responsible to verify all security issues - as defined in your applicable process";
    $subSubHeadingInfoText4 = "--> Follow the link to show security vulnerabilities reported by CT IT Cert: http://mainline.nbgm.siemens.de/Mainline/SecurityInfo.aspx?Component_ID=000";
    $subSubHeadingInfoText5 = "Remark: This link leads to a site which may only list security vulnerabilities becoming known after the clearing date!";

    $subSubHeading2 = "   4.4.3   Patent Situation";
    $subSubHeadingInfoText6 = "Assess patent situation regarding open source software together with your BU patent strategy manager – we cannot expect the community to have clarified the patent situation. ";
    $subSubHeadingInfoText7 = "Patent Notices found in Source Code:";
    $subSubHeadingInfoText8 = "No patent notices found in source scan – patent clearing is responsibility of Siemens projects";
    $section->addText(htmlspecialchars($subHeading), $subHeadingStyle);
    $section->addText(htmlspecialchars($subSubHeading), $subHeadingStyle);
    $section->addText(htmlspecialchars($subSubHeadingInfoText), $subSubHeadingInfoTextStyle);
    $section->addText(htmlspecialchars($subSubHeadingInfoText1), $subSubHeadingInfoTextStyle1);
    $section->addText(htmlspecialChars($subSubHeadingInfoText2), $subSubHeadingInfoTextStyle,$paragraphStyle);
    
    $section->addTextBreak(2);

    $section->addText(htmlspecialchars($subSubHeading1), $subHeadingStyle);
    $section->addText(htmlspecialChars($subSubHeadingInfoText3), $subSubHeadingInfoTextStyle,$paragraphStyle);
    $section->addText(htmlspecialChars($subSubHeadingInfoText4), $subSubHeadingInfoTextStyle,$paragraphStyle);
    $section->addText(htmlspecialChars($subSubHeadingInfoText5), $subSubHeadingInfoTextStyle,$paragraphStyle);
    $section->addTextBreak(2);

    $section->addText(htmlspecialchars($subSubHeading2), $subHeadingStyle);
    $section->addText(htmlspecialchars($subSubHeadingInfoText7), $subSubHeadingInfoTextStyle1,$paragraphStyle);
    $section->addText(htmlspecialchars($subSubHeadingInfoText8), $subSubHeadingInfoTextStyle,$paragraphStyle);

    $section->addTextBreak();
  }



  private function basicForClearingReport($section)
  {
    $paragraphStyle = array("spaceAfter" => 0, "spaceBefore" => 0,"spacing" => 0);          
    $headerStyle = array("name" => Arial,"size" => 18,"bold" => true);
    $heading1 = "5. Basis for Clearing Report";
    $section->addText(htmlspecialchars($heading1), $headerStyle);
    
    $table = $section->addTable("Report Table");

    $cellRowContinue = array("vMerge" => "continue");
    $firstRowStyle = array("name" => Arial, "size" => 12, "bold" => true);
    $rowTextStyle = array("name" => Arial, "size" => 11, "bold" => false);
    $checkBoxStyle = array("name" => Arial,"size" => 10); 								         
		
    $cellRowSpan = array("vMerge" => "restart", "valign" => "top");
    $cellColSpan = array("gridSpan" => 2, "valign" => "center");

    $rowWidth = 200;
    $firstColLen = 3000;	 
    $secondColLen = 5000;	 
    $thirdColLen = 6000;
    $fourthColLen = 2000;

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($firstColLen,$cellRowSpan)->addText(htmlspecialchars("Preparation basis for OSS"),$firstRowStyle,$paragraphStyle);
    $cell = $table->addCell($secondColLen+$thirdColLen,$cellColSpan);
    $cell->addCheckBox("chkBox1", htmlspecialchars("According to Siemens Legally relevant Steps from LCR to Clearing Report"),$rowTextStyle);
    $cell->addCheckBox("chkBox2", htmlspecialchars("no"),$rowTextStyle,$paragraphStyle);
    $cell = $table->addCell($thirdColLen);


    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($firstColLen,$cellRowContinue);
    $cell = $table->addCell($secondColLen+$thirdColLen,$cellColSpan);
    $cell->addCheckBox("chkBox1", htmlspecialchars("According to “Common Principles for Open Source License Interpretation” "),$rowTextStyle);
    $cell->addCheckBox("chkBox2", htmlspecialchars("no"),$rowTextStyle,$paragraphStyle);
   // $cell = $table->addCell($thirdColLen);
    $cell = $table->addCell($fourthColLen);


    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($firstColLen,$cellRowSpan)->addText(htmlspecialchars("OSS Source Code"),$firstRowStyle,$paragraphStyle);
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("Link to Upload page of component;"),$rowTextStyle,$paragraphStyle);
    $cell = $table->addCell($thirdColLen+$fourthColLen,$cellColSpan);
//    $cell = $table->addCell($fourthColLen);
 
    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($firstColLen, $cellRowContinue);
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("MD5 hash value of source code"),$rowTextStyle,$paragraphStyle);
    $cell = $table->addCell($thirdColLen + $fourthColLen,$cellColSpan);

    $table->addRow($rowWidth,$paragraphStyle);
    $cell = $table->addCell($firstColLen)->addText(htmlspecialchars("Result of LCR editor" ),$firstRowStyle,$paragraphStyle);
    $cell = $table->addCell($secondColLen)->addText(htmlspecialchars("Embedded .xml file which can be checked by the LCR Editor is embedded here:"),$rowTextStyle,$paragraphStyle);
    $cell = $table->addCell($thirdColLen + $fourthColLen,$cellColSpan);
  
    $section->addTextBreak();
  }


  private function globalLicenseTable($section)
  {
   
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("6. Global Licenses"), $tableHeading);

    $table = $section->addTable($tablestyle);
    $table->addRow($rowHeight);
    $cell1 = $table->addCell($firstColLen); 
    $cell1->addText("");
    $cell2 = $table->addCell($secondColLen); 
    $cell2->addText("");
    $cell3 = $table->addCell($thirdColLen);
    $cell3->addText("");
    
    $section->addTextBreak(); 
  }


  private function redOSSLicenseTable($section)
  {
  
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("7. Other OSS Licenses (red) - strong copy left Effect or Do not Use Licenses"), $tableHeading);

    $table = $section->addTable($tablestyle);
    $table->addRow($rowHeight);
    $cell1 = $table->addCell($firstColLen); 
    $cell1->addText("");
    $cell2 = $table->addCell($secondColLen); 
    $cell2->addText("");
    $cell3 = $table->addCell($thirdColLen);
    $cell3->addText("");
    
    $section->addTextBreak(); 
  }


  private function yellowOSSLicenseTable($section)
  {
  
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("8. Other OSS Licenses (yellow) - additional obligations to common rules"), $tableHeading);

    $table = $section->addTable($tablestyle);
    $table->addRow($rowHeight);
    $cell1 = $table->addCell($firstColLen); 
    $cell1->addText("");
    $cell2 = $table->addCell($secondColLen); 
    $cell2->addText("");
    $cell3 = $table->addCell($thirdColLen);
    $cell3->addText("");
    $section->addTextBreak(); 
  }
  
  
  private function whiteOSSLicenseTable($section,$contents)
  {
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("9. Other OSS Licenses (white) - only common rules"), $tableHeading);
    $table = $section->addTable($tablestyle);
    foreach($contents["licenses"]["statements"] as $licenseStatement){
      $table->addRow($rowHeight);
      $cell1 = $table->addCell($firstColLen); 
      $cell1->addText(htmlspecialchars($licenseStatement["content"]));
      $cell2 = $table->addCell($secondColLen); 
      $licenseText = str_replace("\n", "<w:br/>", htmlspecialchars($licenseStatement["text"])); // replace new line character 
      $cell2->addText($licenseText);
      $cell3 = $table->addCell($thirdColLen);
      foreach($licenseStatement["files"] as $fileName){ 
         $cell3->addText(htmlspecialchars($fileName));
      }
    }
    $section->addTextBreak(); 
  }
  
  
  private function acknowledgementTable($section,$contents)
  {
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("10. Acknowledgements"), $tableHeading);
    $table = $section->addTable($tablestyle);
    $table->addRow($rowHeight);
    $cell1 = $table->addCell(3500); 
    $cell1->addText(htmlspecialchars("ID of acknowledgements"));
    $cell2 = $table->addCell(5000); 
    $cell2->addText(htmlspecialchars("Text of acknowledgements"));
    $cell3 = $table->addCell(5000);
    $cell3->addText(htmlspecialchars("Reference to the license"));
    $section->addTextBreak(); 
  }

  
  private function copyrightTable($section,$contents)
  {
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("11. Copyrights"), $tableHeading);
    $table = $section->addTable($tablestyle);
    foreach($contents["copyrights"]["statements"] as $copyrightStatement){
      $table->addRow($rowHeight);
      $cell1 = $table->addCell(9500); 
      $cell1->addText(htmlspecialchars($copyrightStatement["content"]));
      $cell2 = $table->addCell(4000);
      foreach($copyrightStatement["files"] as $fileName){ 
        $cell2->addText(htmlspecialchars($fileName));
      }
    }
    $section->addTextBreak(); 
  }
  
  
  private function eccTable($section,$contents)
  {
  
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("12. Export restrictions"), $tableHeading);
    $table = $section->addTable($tablestyle);
    foreach($contents["ecc"]["statements"] as $eccStatement){
      $table->addRow($rowHeight);
      $cell1 = $table->addCell(9500); 
      $cell1->addText(htmlspecialchars($eccStatement["content"]));
      $cell2 = $table->addCell(4000);
      foreach($eccStatement["files"] as $fileName){ 
        $cell2->addText(htmlspecialchars($fileName));
      }
    }
    $section->addTextBreak(); 
  }
  
  
  private function ipTable($section,$contents)
  {
  
    $tableHeading = array("color" => "#000000", "size" => 18, "bold" => true, "name" => "Arial");
    $rowHeight = 500;
    $firstColLen = 2000;
    $secondColLen = 7500;
    $thirdColLen = 4000;
    
    $tablestyle = array("name" => "Arial", "cellSpacing" => 5, "borderSize" => 2);
    $section->addText(htmlspecialchars("13. Intellectual property"), $tableHeading);
    $table = $section->addTable($tablestyle);
    foreach($contents["ip"]["statements"] as $ipStatement){
      $table->addRow($rowHeight);
      $cell1 = $table->addCell(9500); 
      $cell1->addText(htmlspecialchars($ipStatement["content"]));
      $cell2 = $table->addCell(4000);
      foreach($ipStatement["files"] as $fileName){ 
       $cell2->addText(htmlspecialchars($fileName));
      }
    }
    $section->addTextBreak(); 
  }


  private function writeReport($contents, $uploadId)
  {
    global $SysConf;
    $packageName = $this->uploadDao->getUpload($uploadId)->getFilename();

    $fileBase = $SysConf["FOSSOLOGY"]["path"]."/report/";
    if(!is_dir($fileBase)) {
     mkdir($fileBase, 0777, true);
    }
    //$styleTable = array("borderSize" => 6, "borderColor" => "000000", "cellMargin" => 80,"spaceBefore" => 0,"spaceAfter" => 0,"spacing" => 0);
    $styleTable = array("borderSize"=>0,"borderColor"=>"000000", "cellMargin"=>0, "spaceBefore" => 0, "spaceAfter" => 0,"spacing" => 0);
    //$paragraphStyle = array("spaceAfter" => 0, "spaceBefore" => 0,"spacing" => 0);          
		 	
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->addTableStyle("Report Table", $styleTable);
    //$phpWord->addParagraphStyle("paragraph_default",$paragraphStyle);
    $section = $phpWord->createSection(array("orientation"=>"landscape"));

    $this->headerGeneration($section);
    $this->headingOne($section);
	
    $this->summeryTable($section);
    $this->clearingProtocolChangeLogTable($section);	 
    $this->functionalityTable($section);
    $this->assessmentSummeryTable($section);
    $this->todoTable($section);
    $this->todoObliTable($section);	 
    $this->todoObliList($section);	 
    $this->forOtherTodos($section);
    $this->basicForClearingReport($section);
    $this->globalLicenseTable($section);
    $this->redOSSLicenseTable($section);
    $this->yellowOSSLicenseTable($section);
    $this->whiteOSSLicenseTable($section,$contents);
    $this->acknowledgementTable($section);
    $this->copyrightTable($section,$contents);
    $this->eccTable($section,$contents);
    $this->ipTable($section,$contents);
    $this->footerGeneration($section);

    $fileName = $fileBase. "$packageName"."_clearing_report_".date("D_M_d_m_Y_h_i_s").".docx" ;  
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "Word2007");
    $objWriter->save($fileName);

    $this->updateReportTable($uploadId, $this->jobId, $fileName);
    return true;
  }

   private function updateReportTable($uploadId, $jobId, $filename){
   $this->dbManager->getSingleRow("INSERT INTO reportgen(upload_fk, job_fk, filepath) VALUES($1,$2,$3)", array($uploadId, $jobId, $filename), __METHOD__);
  }

}

$agent = new ReportAgent();
$agent->scheduler_connect();
$agent->run_scheduler_event_loop();
$agent->scheduler_disconnect(0);
