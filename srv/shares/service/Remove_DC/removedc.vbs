REM    ========================================================== 
REM                GUI Metadata Cleanup Utility 
REM             Written By Clay Perrine 
REM                          Version 2.5 
REM    ========================================================== 
REM     This tool is furnished "AS IS". NO warranty is expressed or Implied. 
 
on error resume next 
dim objRoot,oDC,sPath,outval,oDCSelect,objConfiguration,objContainer,errval,ODCPath,ckdcPath,myObj,comparename 
 
rem =======This gets the name of the computer that the script is run on ====== 
 
Set sh = CreateObject("WScript.Shell") 
key= "HKEY_LOCAL_MACHINE" 
computerName = sh.RegRead(key & "\SYSTEM\CurrentControlSet\Control\ComputerName\ComputerName\ComputerName") 
 
rem === Get the default naming context of the domain==== 
 
set objRoot=GetObject("LDAP://RootDSE") 
sPath = "LDAP://OU=Domain Controllers," & objRoot.Get("defaultNamingContext") 
 
rem === Get the list of domain controllers==== 
 
Set objConfiguration = GetObject(sPath) 
For Each objContainer in objConfiguration 
    outval = outval & vbtab &  objContainer.Name & VBCRLF 
Next 
outval = Replace(outval, "CN=", "") 
 
rem ==Retrieve the name of the broken DC from the user and verify it's not this DC.=== 
 
oDCSelect= InputBox (outval," Enter the computer name to be removed","") 
comparename = UCase(oDCSelect) 
 
if comparename = computerName then 
    msgbox "The Domain Controller you entered is the machine that is running this script." & vbcrlf & _ 
        "You cannot clean up the metadata for the machine that is running the script!",,"Metadata Cleanup Utility Error." 
    wscript.quit 
End If 
 
sPath = "LDAP://OU=Domain Controllers," & objRoot.Get("defaultNamingContext") 
Set objConfiguration = GetObject(sPath) 
 
For Each objContainer in objConfiguration 
    Err.Clear 
    ckdcPath = "LDAP://" & "CN=" & oDCSelect & ",OU=Domain Controllers," & objRoot.Get("defaultNamingContext") 
    set myObj=GetObject(ckdcPath) 
    If err.number <>0 Then 
        errval= 1 
    End If 
Next 
 
If errval = 1 then 
    msgbox "The Domain Controller you entered was not found in the Active Directory",,"Metadata Cleanup Utility Error." 
    wscript.quit 
End If 
 
abort = msgbox ("You are about to remove all metadata for the server " & oDCSelect & "! Are you sure?",4404,"WARNING!!") 
if abort <> 6 then 
    msgbox "Metadata Cleanup Aborted.",,"Metadata Cleanup Utility Error." 
    wscript.quit 
end if 
 
oDCSelect = "CN=" & oDCSelect 
ODCPath ="LDAP://" & oDCselect & ",OU=Domain Controllers," & objRoot.Get("defaultNamingContext") 
sSitelist = "LDAP://CN=Sites,CN=Configuration," & objRoot.Get("defaultNamingContext") 
Set objConfiguration = GetObject(sSitelist) 
For Each objContainer in objConfiguration 
    Err.Clear 
    sitePath = "LDAP://" & oDCSelect & ",CN=Servers," &  objContainer.Name & ",CN=Sites,CN=Configuration," & _ 
        objRoot.Get("defaultNamingContext") 
    set myObj=GetObject(sitePath) 
    If err.number = 0 Then 
        siteval = sitePath 
    End If     
Next 
 
sFRSSysvolList = "LDAP://CN=Domain System Volume (SYSVOL share),CN=File Replication Service,CN=System," & _ 
    objRoot.Get("defaultNamingContext") 
Set objConfiguration = GetObject(sFRSSysvolList) 
 
For Each objContainer in objConfiguration 
    Err.Clear 
    SYSVOLPath = "LDAP://" & oDCSelect & ",CN=Domain System Volume (SYSVOL share),CN=File Replication Service,CN=System," & _ 
        objRoot.Get("defaultNamingContext") 
    set myObj=GetObject(SYSVOLPath) 
    If err.number = 0 Then 
        SYSVOLval = SYSVOLPath 
    End If 
Next 
 
SiteList = Replace(sSitelist, "LDAP://", "") 
VarSitelist = "LDAP://CN=Sites,CN=Configuration," & objRoot.Get("defaultNamingContext") 
Set SiteConfiguration = GetObject(VarSitelist) 
 
For Each SiteContainer in SiteConfiguration 
    Sitevar = SiteContainer.Name 
    VarPath ="LDAP://OU=Domain Controllers," & objRoot.Get("defaultNamingContext") 
    Set DCConfiguration = GetObject(VarPath) 
    For Each DomContainer in DCConfiguration 
        DCVar = DomContainer.Name 
        strFromServer = "" 
        NTDSPATH =  DCVar & ",CN=Servers," & SiteVar & "," & SiteList 
        GuidPath = "LDAP://CN=NTDS Settings,"& NTDSPATH  
        Set objCheck = GetObject(NTDSPATH) 
        For Each CheckContainer in objCheck 
rem ====check for valid site paths ======================= 
            ldapntdspath = "LDAP://" & NTDSPATH 
            Err.Clear 
            set exists=GetObject(ldapntdspath) 
            If err.number = 0 Then 
                Set oGuidGet = GetObject(GuidPath) 
                For Each objContainer in oGuidGet 
                    oGuid = objContainer.Name 
                    oGuidPath = "LDAP://" & oGuid & ",CN=NTDS Settings," & NTDSPATH   
                    Set objSitelink = GetObject(oGuidPath) 
                    objSiteLink.GetInfo 
                    strFromServer = objSiteLink.Get("fromServer") 
                    ispresent = Instr(1,strFromServer,oDCSelect,1) 
 
                    if ispresent <> 0 then 
                        Set objReplLinkVal = GetObject(oGuidPath) 
                        objReplLinkVal.DeleteObject(0) 
                    end if 
                next 
 
                sitedelval = "CN=" & comparename & ",CN=Servers," & SiteVar & "," & SiteList 
                if sitedelval = ntdspath then 
                    Set objguidpath = GetObject(guidpath) 
                    objguidpath.DeleteObject(0) 
                    Set objntdspath = GetObject(ldapntdspath) 
                    objntdspath.DeleteObject(0) 
                end if 
            End If 
        next 
    next 
next 
Set AccountObject = GetObject(ckdcPath) 
temp=Accountobject.Get ("userAccountControl") 
AccountObject.Put "userAccountControl", "4096" 
AccountObject.SetInfo 
Set objFRSSysvol = GetObject(SYSVOLval) 
objFRSSysvol.DeleteObject(0) 
Set objComputer = GetObject(ckdcPath) 
objComputer.DeleteObject(0) 
Set objConfig = GetObject(siteval) 
objConfig.DeleteObject(0) 
oDCSelect = Replace(oDCSelect, "CN=", "") 
msgval = "Metadata Cleanup Completed for " & oDCSelect 
msgbox  msgval,,"Notice." 
wscript.quit 
