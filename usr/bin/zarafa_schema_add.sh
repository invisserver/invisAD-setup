#!/bin/bash
# Kategorie: setup
# Prepare and apply Zarafa AD schema extensions to Samba 4 AD
# Working with LDIFs from Zarafa ADS 7.1
#
# Copyright (C) Björn Baumbach <bb@sernet.de> 2012-2013
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

test ${BASH_VERSINFO[0]} -lt 4 && {
	echo "WARNING: You should run this script in a bash newer than bash 4.0"
	echo "Older bash versions can lead to unexpected errors."
	echo ""
	echo "Press Ctrl+C to abort the script."

	for i in $(seq 10 -1 1) ; do
		sleep 1
		test $? -ne 0 && {
			exit 1
		}
		echo $i
	done
}

locales="401 404 405 406 407 408 409 40B 40C 40D 40E 410 411 412 413 414 415 416 419 41D 41F 804 816 C04 C0A"
display_specs="user group contact computer organizationalUnit zarafaDynamicGroup zarafaAddresslist"

userdisplay="adminMultiselectPropertyPages:<pno>,{1334AA70-28EC-47CF-907F-8DF012B5AE94}
adminMultiselectPropertyPages:<pno>,{A18D61A6-6BD4-4632-B79B-3B43A68EC1DC}
adminPropertyPages:<pno>,{1334AA70-28EC-47CF-907F-8DF012B5AE94}
adminPropertyPages:<pno>,{A18D61A6-6BD4-4632-B79B-3B43A68EC1DC}
createWizardExt:<pno>,{A7CDACD4-7C10-41e4-A10F-4A9F1E0A889D}"
groupdisplay="adminMultiselectPropertyPages:<pno>,{D4A7414C-9247-11DE-9C89-93CD56D89593}
adminPropertyPages:<pno>,{D4A7414C-9247-11DE-9C89-93CD56D89593}
createWizardExt:<pno>,{EBD34B88-9240-11DE-81F3-6B9656D89593}"
contactdisplay="adminMultiselectPropertyPages:<pno>,{CB9726C4-92F8-11DE-93A8-968A55D89593}
adminPropertyPages:<pno>,{CB9726C4-92F8-11DE-93A8-968A55D89593}
createWizardExt:<pno>,{46F5843C-9245-11DE-960C-9DB956D89593}"
computerdisplay="adminMultiselectPropertyPages:<pno>,{4C84BED1-6E24-4f18-A50E-E88D8BF73355}
adminPropertyPages:<pno>,{4C84BED1-6E24-4f18-A50E-E88D8BF73355}
createWizardExt:<pno>,{37E32183-4CE6-4a0b-A39C-34B25A7A10BD}"
organizationalUnitdisplay="adminMultiselectPropertyPages:<pno>,{E872F718-387A-435B-9FE7-1EDC2C08AD3E}
adminMultiselectPropertyPages:<pno>,{BC955538-46DF-11DF-B673-1C8156D89593}
adminMultiselectPropertyPages:<pno>,{0D75CE1A-C154-4769-80B5-B0E88E7D4EC6}
adminPropertyPages:<pno>,{E872F718-387A-435B-9FE7-1EDC2C08AD3E}
adminPropertyPages:<pno>,{BC955538-46DF-11DF-B673-1C8156D89593}
adminPropertyPages:<pno>,{0D75CE1A-C154-4769-80B5-B0E88E7D4EC6}
createWizardExt:<pno>,{FDF497ED-0647-4928-AA3C-7339E21A3E84}"
zarafaDynamicGroupdisplay="adminMultiselectPropertyPages:<pno>,{05FF3F7E-92F2-11DE-897C-406455D89593}
adminPropertyPages:<pno>,{48A3315E-9BA9-11DE-A055-077C56D89593}
adminPropertyPages:<pno>,{05FF3F7E-92F2-11DE-897C-406455D89593}
createWizardExt:<pno>,{F870F6E6-92EB-11DE-A9B8-B1C456D89593}
creationWizard:{58BDB81C-9635-11DE-BFEB-422256D89593}"
zarafaAddresslistdisplay="adminMultiselectPropertyPages:<pno>,{4B8052B0-92F5-11DE-84EA-8C7655D89593}
adminPropertyPages:<pno>,{4F8AD22E-9BA9-11DE-88EE-2A7C56D89593}
adminPropertyPages:<pno>,{4B8052B0-92F5-11DE-84EA-8C7655D89593}
createWizardExt:<pno>,{AA7E00F6-92EF-11DE-B3DE-425255D89593}
creationWizard:{A6F32300-9635-11DE-A9A3-9E2356D89593}"

print_usage()
{
	echo "Usage:"
	echo "  $0 <domain dn> <path to zarafa ldf directory> <options>"
	echo "		-H <url>                     Database URL"
	echo "		-U <username>%<password>     Set the network username"
	echo "		-v			     verbose"
	echo "		-writechanges		     without this option we"
	echo "					     do not make any changes"
	echo "					     on the database"
	echo "		-dontclean		     do not cleanup temporary"
	echo "					     files"
	echo ""
	echo "Examples:"
	echo "  $0 DC=SAMDOM,DC=EXAMPLE,DC=PRIVATE \\
		./ \\
		-v \\
		-H /usr/local/samba/private/sam.ldb"
	echo "  $0 DC=samdom2,DC=example,DC=private \\
		/home/jesus/myZarafaLDF_Files \\
		-H ldap://mydc.samdom2.example.private \\
		-U Administrator%sTR0ngPassWD \\
		-writechanges"
}

verbose()
{
	test "x$verbose" = "x0" && {
		return 0
	}

	return 1
}

dontclean()
{
	test "x$dontclean" = "x0" && {
		return 0
	}

	return 1
}

get_page_no()
{
	$ldbsearch --sorted $url $userpass \
		-b "CN=${display_spec}-Display,CN=${locale},CN=DisplaySpecifiers,CN=Configuration,${domaindn}" > ${display_spec}_${locale}.ldif
	test "x$?" = "x0" || {
		echo "Error: Can not find CN=${display_spec}-Display,CN=${locale},CN=DisplaySpecifiers,CN=Configuration,${domaindn}" >&2
		return 1
	}

	page_no=$(grep ${page} ${display_spec}_${locale}.ldif | \
		cut -d"," -f 1 | \
		cut -d" " -f 2 | \
		sort -n | \
		tail -1)
	dontclean || rm -f ${display_spec}_${locale}.ldif

	#check if page_no is a digit and not a ID
	#creationWizard attribute has no page_no
	page_no_wo_digit=$(echo $page_no | sed "s/[[:digit:]]//g")
	test "x$page_no_wo_digit" = "x" || {
		return 0
	}

	#test if we make the first entry
	test "x$page_no" = "x" && {
		page_no="0"
	}

	#print next page_no
	echo $[$page_no + 1]
}

domaindn="$1" # e.g. DC=S4DOM,DC=TESTDOM,DC=PRIVATE
test "x$domaindn" = "x" && {
        echo "Error: Please select domain dn"
	print_usage
        exit 1
}

readlink="$(which readlink)"
test "x$readlink" = "x" && {
	echo "Error: Can not find readlink"
	exit 1
}

ldf_dir="$(readlink -e $2)"
test "x$ldf_dir" = "x" && {
	echo "Error: Please select a ldf directory"
	print_usage
	exit 1
}

test -d "$ldf_dir" || {
	echo "Error: $ldf_dir is not a directory"
	print_usage
	exit 1
}

test -r "$ldf_dir/zarafa-ads.ldf" || {
	echo "Error: $ldf_dir/zarafa-ads.ldf is not readable"
	print_usage
	exit 1
}

test -r "$ldf_dir/zarafa-display-ads.ldf"  || {
	echo "Error: $ldf_dir/zarafa-display-ads.ldf is not readable"
	print_usage
	exit 1
}

ldbmodify="$(which ldbmodify)"
test "x$ldbmodify" = "x" && {
	echo "Error: Can not find ldbmodify"
	echo "       Please check your PATH variable"
	exit 1
}
ldbmodsuff=$($ldbmodify --help | grep -- --option | grep smb.conf)
test -z "$ldbmodsuff" && {
	echo "Error: installed version ldbmodify is not supported"
	exit 1
}

ldbsearch="$(which ldbsearch)"
test "x$ldbsearch" = "x" && {
	echo "Error: Can not find ldbsearch" >&2
	echo "       Please check your PATH variable" >&2
	exit 1
}

dos2unix="$(which dos2unix)"
test "x$dos2unix" = "x" && {
	echo "Error: Can not find dos2unix" >&2
	echo "       Please check your PATH variable" >&2
	exit 1
}

workspace="/tmp/zarafa_schema_add_$(date +%s)"
mkdir $workspace
test "x$?" = "x0" || {
	echo "Error: Can not create temporary workspace $tempdir"
	exit 1
}
cd $workspace

writechanges="no"
writecount=0
verbose=1
dontclean=1
argno=1
for arg in "$@" ; do
	case $arg in
	"-U")
		userpass="-U `eval echo '$'$[$argno+1]`"
		;;
	"-H")
		url="-H `eval echo '$'$[$argno+1]`"
		;;
	"-writechanges")
		writechanges="yes"
		;;
	"-v")
		verbose=0
		;;
	"-dontclean")
		dontclean=0
		;;
	"-h")
		print_usage
		exit 0
		;;
	*)
		;;
	esac
	argno=$[$argno + 1]
done

#replace and add some information (see description)
ldf_file="zarafa-ads.ldf"
cp "$ldf_dir/$ldf_file" "$ldf_file.unix"
$dos2unix "$ldf_file.unix" || {
	echo "Error: dos2unix: Failed to convert $ldf_file.unix"
	exit 1
}

cat "$ldf_file.unix" | \
sed -e "s/<SchemaContainerDN>/CN=Schema,CN=Configuration,${domaindn}/" \
    -e "s/ntdsSchemaAdd/add/" \
    -e "s/ntdsSchemaModify/modify/" \
    -e "s/attributeSyntax: 2\.5\.5\.1$/\0\noMObjectClass:: KwwCh3McAIVK/" \
    > "$ldf_file.sed"

dontclean || rm -f "$ldf_file.unix"

#split file on schemaUpdateNow
last=0
updates="$(cat -n "$ldf_file.sed" | grep "schemaUpdateNow: 1" | awk '{ printf "%d\n ", $1+1  }')"
for i in $updates ; do
	verbose && echo "writing $ldf_file.sed.$i"
	head -n $i $ldf_file.sed | \
		tail -n $[ $[ $i ] - $[ $last ] ] > "$ldf_file.sed.$i"

	verbose && echo "Writing $ldf_file.sed.$i changes to $url ..."
	test "x$writechanges" = "xyes" && {
		cat "$ldf_file.sed.$i" | \
			$ldbmodify $url $userpass \
			--option="dsdb:schema update allowed=yes"
		test "x$?" = "x0" || {
			echo "Error: ldbmodify reported an error"
			exit 1
		}
		writecount=$[$writecount + 1]
	}
	verbose && echo "Writecounter: $writecount"
	dontclean || rm -f "$ldf_file.sed.$i"

	last=$i
done
dontclean || rm -f "$ldf_file.sed"

#replace and add some information (see description)
ldf_file="zarafa-display-ads.ldf"
cp "$ldf_dir/$ldf_file" "$ldf_file.unix"
$dos2unix "$ldf_file.unix" || {
        echo "Error: dos2unix: Failed to convert $ldf_file.unix"
        exit 1
}

for locale in $locales ; do
	cat "$ldf_file.unix" | \
	sed -e "s/<SchemaContainerDN>/CN=Schema,CN=Configuration,${domaindn}/" \
	    -e "s/<ConfigurationContainerDN>/CN=${locale},CN=DisplaySpecifiers,CN=Configuration,${domaindn}/" \
	    -e "s/ntdsSchemaAdd/add/" \
	    -e "s/ntdsSchemaModify/modify/" \
	    > "$ldf_file.sed.$locale"

	test "x$writechanges" = "xyes" && {
		verbose && echo "Writing $ldf_file.sed.$locale to $url ..."
		cat "$ldf_file.sed.$locale" | \
			$ldbmodify $url $userpass \
			--option="dsdb:schema update allowed=yes"
		test "x$?" = "x0" || {
			echo "Error: ldbmodify reported an error"
			exit 1
		}
		writecount=$[$writecount + 1]
	}
	verbose && echo "Writecounter: $writecount"
	dontclean || rm -f "$ldf_file.sed.$locale"
done
dontclean || rm -f "$ldf_file.unix"

for display_spec in $display_specs ; do
	for locale in $locales ; do
		dispvar="'$'${display_spec}display"
		eval disp=$(eval echo $dispvar)
		for d in $disp ; do
			page=$(echo $d | cut -d ":" -f 1)
			page_no=$(get_page_no)
			test "x$?" = "x0" || exit 1

			attribute=$(echo $d | sed "s/<pno>/$page_no/")

			verbose && echo -e "\ndn:CN=${display_spec}-Display,CN=${locale},CN=DisplaySpecifiers,CN=Configuration,${domaindn}\nchangetype: modify\nadd: $page\n$attribute\n-\n"
			verbose && echo "Adding $page to CN=${display_spec}-Display,CN=${locale},CN=DisplaySpecifiers,CN=Configuration,${domaindn} ..."
			test "x$writechanges" = "xyes" && {
				echo -e "\ndn:CN=${display_spec}-Display,CN=${locale},CN=DisplaySpecifiers,CN=Configuration,${domaindn}\nchangetype: modify\nadd: $page\n$attribute\n-\n" | $ldbmodify $url $userpass --option="dsdb:schema update allowed=yes"
				test "x$?" = "x0" || {
					echo "Error: Can not modify object" >&2
					exit 1
				}
				writecount=$[$writecount + 1]
			}
			verbose && echo "Writecounter: $writecount"
		done
	done
done

echo -e "\nWrote $writecount changes to $url"

