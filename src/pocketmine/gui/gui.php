<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\gui;

class frmMain extends \wxFrame {

	function onMenuServerExit() {
		$this->Destroy();
	}
  
	function onMenuServerStart() {
		if($this->menuServerStart->GetText() === "S&tart") {
			$this->menuServerStart->SetText("S&top");
			$this->menuServerStart->SetHelp("Stop server ...");
			//$this->menuServerStart->SetBitmap(new \wxBitmap("./images/stop.png", wxBITMAP_TYPE_PNG));
		} else {
			$this->menuServerStart->SetText("S&tart");
			$this->menuServerStart->SetHelp("Start server ...");
			//$this->menuServerStart->SetBitmap(new \wxBitmap("./images/start.png", wxBITMAP_TYPE_PNG));	
		}
	}
  
	function onMenuHelpForums() {
		wxLaunchDefaultBrowser("http://forums.pocketmine.net");
	}
	
	function onMenuOptionsProp() {
		$this->ServerProperties->ShowModal();
	}

	function onMenuHelpAbout() {
		$dlg = new \wxMessageDialog($this, "PocketMine-MP is a sofware for creating Minecraft Pocket Edition servers.\nIt has a Plugin API that enables a developer to extend it and add new features, or change default ones.\n\nThe entire server is done in PHP, and has been tested, profiled and optimized to run smoothly.", "About PocketMine-MP...", wxICON_INFORMATION);
		$dlg->ShowModal();
	}

	function __construct($parent = \Null) {
		parent::__construct($parent, wxID_ANY, "PocketMine-MP {VERSION} server", wxDefaultPosition, new \wxSize( 800,480 ), wxCAPTION|wxCLOSE_BOX|wxMINIMIZE_BOX|wxSYSTEM_MENU|wxTAB_TRAVERSAL );

		$this->SetIcon(new \wxIcon("./images/app.png", wxBITMAP_TYPE_PNG));
		$this->SetSizeHints(wxDefaultSize, wxDefaultSize);
		$this->SetBackgroundColour(new \wxColour(240, 240, 240));
		
		$this->menuBar = new \wxMenuBar();
		
		$this->menuServer = new \wxMenu();
		$this->menuBar->Append($this->menuServer, "&Server");
		$this->menuServerStart = new \wxMenuItem($this->menuServer, wxID_ANY, "S&tart", "Start server ...", wxITEM_NORMAL);
		//$this->menuServerStart->SetBitmap(new \wxBitmap("./images/start.png", wxBITMAP_TYPE_PNG));
		$this->menuServer->Append($this->menuServerStart);
		$this->menuServer->AppendSeparator();
		$this->menuServerExit = new \wxMenuItem($this->menuServer, wxID_ANY, "&Exit", "Exit server ...", wxITEM_NORMAL);
		$this->menuServerExit->SetBitmap(new \wxBitmap("./images/exit.png", wxBITMAP_TYPE_PNG));
		$this->menuServer->Append($this->menuServerExit);	

		$this->menuOptions = new \wxMenu();
		$this->menuBar->Append( $this->menuOptions, "&Options" );
		$this->menuOptionsProp = new \wxMenuItem($this->menuOptions, wxID_ANY, "&Properties", "Edit server.properties file ...", wxITEM_NORMAL);
		$this->menuOptionsProp->SetBitmap(new \wxBitmap("./images/prop.png", wxBITMAP_TYPE_PNG));
		$this->menuOptions->Append($this->menuOptionsProp);
		$this->menuOptions->AppendSeparator();
		$this->menuOptionsPlug = new \wxMenuItem($this->menuOptions, wxID_ANY, "P&lugins", "Open plugins manager ...", wxITEM_NORMAL);
		$this->menuOptionsPlug->SetBitmap(new \wxBitmap("./images/plug.png", wxBITMAP_TYPE_PNG));
		$this->menuOptions->Append($this->menuOptionsPlug);

		$this->menuHelp = new \wxMenu();
		$this->menuBar->Append($this->menuHelp, "&Help");
		$this->menuHelpForums = new \wxMenuItem($this->menuHelp, wxID_ANY, "&Forums", "Open http://forums.pocketmine.net ...", wxITEM_NORMAL);
		$this->menuHelpForums->SetBitmap(new \wxBitmap("./images/link.png", wxBITMAP_TYPE_PNG));
		$this->menuHelp->Append($this->menuHelpForums);
		$this->menuHelp->AppendSeparator();
		$this->menuHelpAbout = new \wxMenuItem($this->menuHelp, wxID_ANY, "&About...", "About PocketMine-MP ...", wxITEM_NORMAL);
		$this->menuHelpAbout->SetBitmap(new \wxBitmap("./images/about.png", wxBITMAP_TYPE_PNG));
		$this->menuHelp->Append($this->menuHelpAbout);
		
		$this->SetMenuBar($this->menuBar);

		$bSizerMain = new \wxBoxSizer(wxHORIZONTAL);
		$bSizerLeft = new \wxBoxSizer(wxVERTICAL);
		$bSizerLeft->SetMinSize(new \wxSize(330, -1));
			$sbSizerStats = new \wxStaticBoxSizer(new \wxStaticBox($this, wxID_ANY, "Stats"),wxVERTICAL);
			$this->sTextRAM = new \wxStaticText($this, wxID_ANY, "Memory use : {MEMORY}", wxDefaultPosition, wxDefaultSize, 0);
			$this->sTextRAM->Wrap(-1);
			$sbSizerStats->Add($this->sTextRAM, 0, wxALL, 5);
				$bSizerTPS = new \wxBoxSizer(wxHORIZONTAL);
				$this->sTextTPS = new \wxStaticText($this, wxID_ANY, "TPS : ", wxDefaultPosition, wxDefaultSize, 0);
				$this->sTextTPS->Wrap(-1);
				$bSizerTPS->Add($this->sTextTPS, 0, wxALL, 5);
				$this->gaugeTPS = new \wxGauge($this, wxID_ANY, 100, wxDefaultPosition, wxDefaultSize, wxGA_HORIZONTAL);
				$this->gaugeTPS->SetValue(75); 
				$bSizerTPS->Add($this->gaugeTPS, 0, wxALL, 5);
				$sbSizerStats->Add($bSizerTPS, 0, wxEXPAND, 5);
			$this->sTextUP = new \wxStaticText($this, wxID_ANY, "Upload : {UP} kB/s", wxDefaultPosition, wxDefaultSize, 0);
			$this->sTextUP->Wrap(-1);
			$sbSizerStats->Add($this->sTextUP, 0, wxALL, 5);
			$this->sTextDOWN = new \wxStaticText($this, wxID_ANY, "Download : {DOWN} kB/s", wxDefaultPosition, wxDefaultSize, 0);
			$this->sTextDOWN->Wrap(-1);
			$sbSizerStats->Add($this->sTextDOWN, 0, wxALL, 5);
			$bSizerLeft->Add($sbSizerStats, 1, wxEXPAND, 5);
			$sbSizerPlayers = new \wxStaticBoxSizer(new \wxStaticBox($this, wxID_ANY, "Players"), wxVERTICAL);
			$aplayers = array( "sekjun9878", "@Intyre", "Brandon15811", "@shogchips", "BlinkSun" );
			$this->lbPlayers = new \wxListBox($this, wxID_ANY, wxDefaultPosition, wxDefaultSize, $aplayers, wxLB_ALWAYS_SB|wxLB_SINGLE|wxLB_SORT|wxSTATIC_BORDER);
				$this->menulbPlayers = new \wxMenu();
				$this->menulbPlayersOp = new \wxMenuItem($this->menulbPlayers, wxID_ANY, "Op", wxEmptyString, wxITEM_NORMAL);
				$this->menulbPlayers->Append( $this->menulbPlayersOp);
				$this->menulbPlayers->AppendSeparator();
				$this->menulbPlayersKick = new \wxMenuItem($this->menulbPlayers, wxID_ANY, "Kick", wxEmptyString, wxITEM_NORMAL);
				$this->menulbPlayers->Append( $this->menulbPlayersKick );
				$this->menulbPlayersBan = new \wxMenuItem($this->menulbPlayers, wxID_ANY, "Ban", wxEmptyString, wxITEM_NORMAL);
				$this->menulbPlayers->Append( $this->menulbPlayersBan );
				$this->menulbPlayersBanIp = new \wxMenuItem($this->menulbPlayers, wxID_ANY, "BanIp", wxEmptyString, wxITEM_NORMAL);
				$this->menulbPlayers->Append( $this->menulbPlayersBanIp);
			$sbSizerPlayers->Add($this->lbPlayers, 1, wxALL|wxEXPAND, 5);
			$bSizerLeft->Add($sbSizerPlayers, 1, wxEXPAND, 5);
			$bSizerInfo = new \wxBoxSizer(wxHORIZONTAL);
			$this->hlinkPM = new \wxHyperlinkCtrl($this, wxID_ANY, "PocketMine-MP {VERSION}", "http://www.pocketmine.net", wxDefaultPosition, wxDefaultSize, wxHL_DEFAULT_STYLE);
			$bSizerInfo->Add($this->hlinkPM, 0, wxALL, 5);
			$this->sTextLicense = new \wxStaticText($this, wxID_ANY, "Distributed under the LGPL License", wxDefaultPosition, wxDefaultSize, 0);
			$this->sTextLicense->Wrap(-1);
			$bSizerInfo->Add($this->sTextLicense, 0, wxALL, 5);
			$bSizerLeft->Add($bSizerInfo, 0, 0, 5);
		$bSizerMain->Add($bSizerLeft, 0, wxALL|wxEXPAND, 5);
		$bSizerRight = new \wxBoxSizer(wxVERTICAL);
		$bSizerRight->SetMinSize(new \wxSize(-1, -1));
			$sbSizerConsole = new \wxStaticBoxSizer(new \wxStaticBox($this, wxID_ANY, "Log and chat"), wxVERTICAL);
			$this->bTextConsole = new \wxTextCtrl($this, wxID_ANY, "[TEST] test line", wxDefaultPosition, wxDefaultSize, wxVSCROLL|wxHSCROLL|wxALWAYS_SHOW_SB|wxSTATIC_BORDER|wxTE_READONLY|wxTE_MULTILINE);
			$sbSizerConsole->Add( $this->bTextConsole, 1, wxEXPAND, 5 );
			$this->bTextSend = new \wxTextCtrl($this, wxID_ANY, wxEmptyString, wxDefaultPosition, wxDefaultSize, wxTE_NO_VSCROLL);
			$sbSizerConsole->Add( $this->bTextSend, 0, wxEXPAND, 5);
			$bSizerRight->Add($sbSizerConsole, 1, wxEXPAND, 5);
		$bSizerMain->Add($bSizerRight, 1, wxALL|wxEXPAND, 5);
			
		$this->SetSizer($bSizerMain);
		$this->Layout();
		
		$sbar = $this->CreateStatusBar(2);
		$sbar->SetStatusText("Welcome to PocketMine-MP.");

		$this->Connect($this->menuServerStart->GetId(), wxEVT_COMMAND_MENU_SELECTED, array($this,"onMenuServerStart"));
		$this->Connect($this->menuServerExit->GetId(), wxEVT_COMMAND_MENU_SELECTED, array($this,"onMenuServerExit"));
		$this->Connect($this->menuHelpForums->GetId(), wxEVT_COMMAND_MENU_SELECTED, array($this,"onMenuHelpForums"));
		$this->Connect($this->menuHelpAbout->GetId(), wxEVT_COMMAND_MENU_SELECTED, array($this,"onMenuHelpAbout"));
		$this->Connect($this->menuOptionsProp->GetId(), wxEVT_COMMAND_MENU_SELECTED, array($this,"onMenuOptionsProp"));
		$this->lbPlayers->Connect(wxEVT_RIGHT_DOWN, array($this,"onMenulbPlayers"));
		
		$this->Centre(wxBOTH);
		
		$this->ServerProperties = new frmProperties($this);
	}
	
	function __destruct() {}
	
	function onMenulbPlayers($event){
		if(\stripos($this->lbPlayers->GetString($this->lbPlayers->GetSelection()), "@") !== \false) {
			$this->menulbPlayersOp->SetText("Deop");
		} else {
			$this->menulbPlayersOp->SetText("Op");
		}
		$this->lbPlayers->PopupMenu($this->menulbPlayers, $event->GetPosition());
	}
}

class frmProperties extends \wxDialog {
	
	function __construct( $parent=\null ){
		parent::__construct ($parent, wxID_ANY, "server.properties", wxDefaultPosition, new \wxSize( 500,300 ), wxDEFAULT_DIALOG_STYLE);
		
		$this->SetSizeHints(wxDefaultSize, wxDefaultSize);
		
		$bSizer = new \wxBoxSizer(wxVERTICAL);
		
		$this->gridProp = new \wxGrid($this, wxID_ANY, wxDefaultPosition, wxDefaultSize, 0);

		$this->gridProp->CreateGrid(25, 1);
		$this->gridProp->EnableEditing(\true);
		$this->gridProp->EnableGridLines(\true);
		$this->gridProp->EnableDragGridSize(\false);
		$this->gridProp->SetMargins(0, 0);

		$this->gridProp->SetColSize(0, 267);
		$this->gridProp->EnableDragColMove(\false);
		$this->gridProp->EnableDragColSize(\false);
		$this->gridProp->SetColLabelSize(30);
		$this->gridProp->SetColLabelValue(0, "Values");
		$this->gridProp->SetColLabelAlignment(wxALIGN_LEFT, wxALIGN_CENTRE);

		$this->gridProp->AutoSizeRows();
		$this->gridProp->EnableDragRowSize(\false);
		$this->gridProp->SetRowLabelSize(200);
		$this->gridProp->SetRowLabelValue(0, "server-name");
		$this->gridProp->SetRowLabelValue(1, "server-port");
		$this->gridProp->SetRowLabelValue(2, "memory-limit");
		$this->gridProp->SetRowLabelValue(3, "gamemode");
		$this->gridProp->SetRowLabelValue(4, "max-players");
		$this->gridProp->SetRowLabelValue(5, "spawn-protection");
		$this->gridProp->SetRowLabelValue(6, "white-list");
		$this->gridProp->SetRowLabelValue(7, "enable-query");
		$this->gridProp->SetRowLabelValue(8, "enable-rcon");
		$this->gridProp->SetRowLabelValue(9, "send-usage");
		$this->gridProp->SetRowLabelValue(10, "motd");
		$this->gridProp->SetRowLabelValue(11, "announce-player-achievements");
		$this->gridProp->SetRowLabelValue(12, "view-distance");
		$this->gridProp->SetRowLabelValue(13, "allow-flight");
		$this->gridProp->SetRowLabelValue(14, "spawn-animals");
		$this->gridProp->SetRowLabelValue(15, "spawn-mobs");
		$this->gridProp->SetRowLabelValue(16, "hardcore");
		$this->gridProp->SetRowLabelValue(17, "pvp");
		$this->gridProp->SetRowLabelValue(18, "difficulty");
		$this->gridProp->SetRowLabelValue(19, "generator-settings");
		$this->gridProp->SetRowLabelValue(20, "level-name");
		$this->gridProp->SetRowLabelValue(21, "level-seed");
		$this->gridProp->SetRowLabelValue(22, "level-type");
		$this->gridProp->SetRowLabelValue(23, "rcon.password");
		$this->gridProp->SetRowLabelValue(24, "auto-save");
		$this->gridProp->SetRowLabelAlignment(wxALIGN_LEFT, wxALIGN_CENTRE);

		$this->gridProp->SetDefaultCellAlignment(wxALIGN_LEFT, wxALIGN_TOP);
		$bSizer->Add($this->gridProp, 1, wxALL|wxEXPAND, 5);

		$sdbSizer = new \wxStdDialogButtonSizer();
		$this->sdbSizerSave = new \wxButton($this, wxID_SAVE);
		$sdbSizer->AddButton($this->sdbSizerSave );
		$this->sdbSizerCancel = new \wxButton( $this, wxID_CANCEL);
		$sdbSizer->AddButton($this->sdbSizerCancel);
		$sdbSizer->Realize();
		$bSizer->Add($sdbSizer, 0, wxEXPAND, 5);

		$this->SetSizer($bSizer);
		$this->Layout();

		$this->Centre(wxBOTH);
		
		$this->sdbSizerCancel->Connect(wxEVT_COMMAND_BUTTON_CLICKED, array($this, "OnCancelButtonClick"));
		$this->sdbSizerSave->Connect(wxEVT_COMMAND_BUTTON_CLICKED, array($this, "OnSaveButtonClick"));


	}
	
	function OnCancelButtonClick($event){
		$event->Skip();
	}
	
	function OnSaveButtonClick($event){
		$event->Skip();
	}
	
	
	function __destruct( ){
	}
	
}

class PocketMineGui extends \wxApp {
	function OnInit() {
		wxInitAllImageHandlers();
		$this->mf = new frmMain();
		$this->mf->Show();
		return \true;
	}
	function OnExit(){
		//TODO: call normal server stop procedure
		return \false;
	}
}

$app = new PocketMineGui();
\wxApp::SetInstance($app);
wxEntry();

?>
