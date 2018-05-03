# CCSMiner


echo "---------install-packages----------"
sudo apt install -y libmicrohttpd-dev libssl-dev cmake build-essential libhwloc-dev lm-sensors git ssh php php7.0-curl clinfo
echo "---------install-dist-upgrade----------"
sudo apt dist-upgrade -y
echo "---------install-update----------"
sudo apt update -y
echo "---------install-upgrade----------"
sudo apt upgrade -y
echo "---------reboot----------"
sudo reboot

wget --referer=http://support.amd.com https://www2.ati.com/drivers/linux/beta/ubuntu/amdgpu-pro-17.40.2712-510357.tar.xz
tar -Jxvf amdgpu-pro-17.40.2712-510357.tar.xz
sudo chmod 777 -R 
./amdgpu-pro-install -y --compute

echo "vm.nr_hugepages=128" >> /etc/sysctl.conf
sysctl -p


echo "soft memlock 262144" >> /etc/security/limits.conf
echo "hard memlock 262144" >> /etc/security/limits.conf

sudo reboot


git clone https://github.com/LordKain001/CCSMiner
