#!/usr/bin/python
# Name: Chen Liang
# College: Dublin Institute of Technology
# Date: 06 Apr 2012
# Description: SSH client program simulation

import socket
import os
import sys
import getpass

from Ticket import TicketGrantingTicket
from Ticket import ServiceTicket

HOST = socket.gethostname()  # The host name
IP = socket.gethostbyname(HOST)  # The IP address
KRB_HOST = "127.0.0.1"  # Kerberos key Distribution Centre Hostname
KRB_PORT = 50001  # The KDC simulation's port number
SSHD_HOST = "127.0.0.1"  # SSH Server Hostname
SSHD_PORT = 2222  # The SSH server simulation's port number

# Make sure the end-user has registered in KDC


def authentication():
    try:
        krb_sok = socket.socket(
            socket.AF_INET, socket.SOCK_STREAM)  # KRB socket
        krb_sok.connect((KRB_HOST, KRB_PORT))
        print('Connecting to', HOST, '...')

        name = raw_input('Username: ')
        password = getpass.getpass('Password: ')

        krb_sok.send('req_tgt;' + name + ';' + password)
        # Recieve returned ticket information
        ticket = krb_sok.recv(1024)

        if ticket != 'fail':
            # Convert into ticket
            save_tgt(ticket)
            krb_sok.close()
            return True
        else:
            krb_sok.close()
            return False
    except (KeyboardInterrupt, SystemExit):
        print("existing...")
        krb_sok.close
    else:
        pass

# Save ticket granting ticket from KDC


def save_tgt(tgt_info):
    # Set ticket granting ticket content in memory
    tgt.set_ticket_info(tgt_info)
    print('Recieved and saved Ticket Granting Ticket.')

    # Save ticket granting ticket content in file
    f = open('tgt.key', 'w')
    f.write(tgt_info)
    f.close()


def get_tgt_info():
    print('\nTicket Granting Ticket:')
    print('Username:', tgt.get_username())
    print('Password:', tgt.get_password())
    print('Role:', tgt.get_role())
    print('Domain:', tgt.get_domain())
    print('Ticket Create Date:', tgt.get_create_date())
    print('Ticket Expire Date:', tgt.get_expire_date())
    print('Key:', tgt.get_md5sum())
    print('\n')

# Get service ticket from KDC to access services


def request_service_ticket():
    krb_sok = socket.socket(socket.AF_INET, socket.SOCK_STREAM)  # KRB socket
    f = open('tgt.key', 'r')
    tgt_info = f.readline()
    f.close()

    krb_sok.connect((KRB_HOST, KRB_PORT))
    print('Connecting to', HOST, '...')
    krb_sok.send('req_st;' + tgt_info)

    answer = krb_sok.recv(1024)

    if answer == 'fail':
        krb_sok.close()
        return 'fail'
    else:
        krb_sok.send('ssh')
        st_info = krb_sok.recv(1024)
        krb_sok.close()
        return st_info

# Simple simulation of ls


def ls_sim():
    for dirname, dirnames, filenames in os.walk('.'):
        for subdirname in dirnames:
            print(os.path.join(dirname, subdirname))
        for filename in filenames:
            print(os.path.join(dirname, filename))

# Simluation of a local Unix/Linux shell


def shell_sim():
    while True:
        command = raw_input('local shell~>')
        if command == 'ls':
            ls_sim()
        elif command == 'exit':
            sys.exit(0)
        elif command == 'hostname':
            print(HOST)
        elif command == 'ifconfig':
            print(IP)
        elif command == 'ssh foreign.virtual.vm':
            st_info = request_service_ticket()  # Request Service Ticket
            if st_info != 'fail':
                ssh_sim(st_info)  # Simulation of SSH
            else:
                print('Fail to request service ticket')
        elif command == 'klist':
            get_tgt_info()
        else:
            print('ls\tifconfig\thostname\texit\tklist\tssh foreign.virtual.vm')


def ssh_sim(st_info):
    ssh = socket.socket(socket.AF_INET, socket.SOCK_STREAM)  # SSH socket
    ssh.connect((SSHD_HOST, SSHD_PORT))
    # Send service ticket to ssh server
    ssh.send(st_info)

    # Recieve single sign-on reply
    shell_name = ssh.recv(1024)

    if shell_name != 'fail':
        results = ''

        while results != 'exit_confirm':
            command = raw_input(shell_name)
            ssh.send(command)
            results = ssh.recv(1024)
            if results != 'exit_confirm':
                print(results)
            else:
                pass

    else:
        print('Service ticket failed to authenticate')

    ssh.close()
    print('exiting...')


if __name__ == '__main__':
    tgt = TicketGrantingTicket()  # Ticket granting ticket
    st = ServiceTicket()  # Service Ticket

    try:
        if authentication():
            shell_sim()  # Start shell simulation
        else:
            print('Unsuccessful authentication.')
    except (KeyboardInterrupt, SystemExit):
        print("\nexisting...")
        sys.exit(1)
    else:
        sys.exit(1)
