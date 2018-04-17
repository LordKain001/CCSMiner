#!/bin/bash
sudo cp -v ccsMiner.service /etc/systemd/system
sudo cp -v ccsMiner.service /etc/systemd/system/multi-user.target.wants/
