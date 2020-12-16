#!/usr/bin/env python
# -*- coding: utf-8 -*-
# vim: tabstop=8 expandtab shiftwidth=4 softtabstop=4
from __future__ import print_function
from __future__ import unicode_literals
import kopano
import sys


def opt_args():
    parser = kopano.parser('skpcufm')
    parser.add_option(
        "--dry-run",
        dest="dryrun",
        action="store_true",
        help="run the script without executing any actions")

    options, args = parser.parse_args()
    if not options.users:
        parser.print_help()
        exit()
    else:
        return options


def main():
    options = opt_args()
    for user in kopano.Server(options).users():
        print ('Checking user store: {}'.format(user.name))
        for f in user.folders(recurse=True):
            if f.container_class == 'IPF.Imap':
                print ('{}: IPF.Imap folder detected'.format(f.name))
                if not options.dryrun:
                    print (
                        '{}: Changing container_class to IPF.Note'.format(f.name))
                    f.container_class = 'IPF.Note'


if __name__ == "__main__":
    main()
