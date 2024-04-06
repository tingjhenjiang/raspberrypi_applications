#%%
# ref:
# adguard home
# https://d3ward.github.io/toolz/adblock
# https://gitlab.com/malware-filter/urlhaus-filter
# https://github.com/uBlockOrigin/uAssets
# https://github.com/easylist/easylist
# https://github.com/collinbarrett/FilterLists/blob/main/services/Directory/data/FilterList.json
# https://github.com/badmojr/1Hosts

import requests
import asyncio
import time
from functools import reduce
from multiprocessing.dummy import Pool as ThreadPool
import re,os,json
from pathlib import Path

# %%
# print(
#     re.search(r"(?:0\.0\.0\.0|127\.0\.0\.1|127\.0\.0\.0|::1)*[\s\t]*([^\s#:]+)*","0.0.0.0 c.bigmir.net #[WebBug]").groups()
# )
# print(
#     re.match("^[#|!]"," Source: https://urlhaus.abuse.ch/api/")
# )
#%%
threads = os.cpu_count()
adblock_srcs = [
    "https://raw.githubusercontent.com/AdguardTeam/cname-trackers/master/data/combined_disguised_trackers_justdomains.txt",
    "https://cdn.jsdelivr.net/gh/kboghdady/youTube_ads_4_pi-hole/black.list",
    "https://raw.githubusercontent.com/AdguardTeam/cname-trackers/master/data/combined_disguised_clickthroughs_justdomains.txt",
    "https://raw.githubusercontent.com/AdguardTeam/cname-trackers/master/data/combined_disguised_ads_justdomains.txt",
    "https://raw.githubusercontent.com/AdguardTeam/cname-trackers/master/data/combined_disguised_microsites_justdomains.txt",
    # # url per row
    "http://sysctl.org/cameleon/hosts",
    "https://winhelp2002.mvps.org/hosts.txt",
    "https://adaway.org/hosts.txt",
    "https://pgl.yoyo.org/as/serverlist.php?hostformat=hosts&showintro=1&mimetype=plaintext",
    "https://cdn.jsdelivr.net/gh/hoshsadiq/adblock-nocoin-list/hosts.txt",
    # second column
    "https://malware-filter.gitlab.io/malware-filter/urlhaus-filter-domains.txt",
    "https://raw.githubusercontent.com/ewpratten/youtube_ad_blocklist/master/blocklist.txt",
    "https://www.kalfaoglu.net/you/my-youtube-ads-list.txt",
    "https://pgl.yoyo.org/adservers/serverlist.php?hostformat=dnsmasq",
    "https://raw.githubusercontent.com/easylist/easylist/master/easylist/easylist_adservers.txt",
    "https://raw.githubusercontent.com/Spam404/lists/master/adblock-list.txt",
    "https://raw.githubusercontent.com/anudeepND/blacklist/master/adservers.txt",
    "https://gitlab.com/ZeroDot1/CoinBlockerLists/-/raw/master/list.txt?ref_type=heads",
    "https://raw.githubusercontent.com/EFForg/privacybadger/master/src/data/cname_domains.json",
    "https://o0.pages.dev/Lite/domains.txt",
]

# "https://malware-filter.gitlab.io/malware-filter/urlhaus-filter.txt",
# "https://malware-filter.gitlab.io/malware-filter/urlhaus-filter-hosts.txt",
# "https://malware-filter.gitlab.io/malware-filter/urlhaus-filter-dnsmasq.conf",
# "https://malware-filter.gitlab.io/malware-filter/urlhaus-filter-dnscrypt-blocked-names.txt",
# "https://malware-filter.gitlab.io/malware-filter/urlhaus-filter-dnscrypt-blocked-ips.txt",

def merge_dict(dicta, dictb):
    return {**dicta, **dictb}

def preprocess_one_row(rowtext):
    try:
        if re.match(r"^[#\|!@]|/\(",rowtext) is not None:
            return None
        else:
            tempv = re.search(r"(?:0\.0\.0\.0|127\.0\.0\.1|::1)*[\s\t]*([^\s#:]+)*",rowtext).groups()[0]
            if tempv is not None:
                tempv = tempv.strip()
            return tempv
    except Exception as e:
        print(f"error at {rowtext}")
        raise(e)

def txt_to_hostlist(srctxt, srcurl=None):
    if srcurl in ["https://malware-filter.gitlab.io/malware-filter/urlhaus-filter-dnsmasq.conf",
                  "https://pgl.yoyo.org/adservers/serverlist.php?hostformat=dnsmasq"]:
        srctxt = srctxt.replace("address=/","").replace("/0.0.0.0","").replace("/127.0.0.1","")
    if srcurl in ["https://raw.githubusercontent.com/easylist/easylist/master/easylist/easylist_adservers.txt","https://raw.githubusercontent.com/Spam404/lists/master/adblock-list.txt"]:
        srctxt = srctxt.replace("||","").replace("^$third-party","").replace("^\n","\n")
    if srcurl in ["https://raw.githubusercontent.com/EFForg/privacybadger/master/src/data/cname_domains.json"]:
        hosts = json.loads(srctxt)
        hosts = list(hosts.values())
    else:
        hosts = srctxt.split("\n")
        p = ThreadPool(threads)
        hosts = p.map(preprocess_one_row, hosts)
        hosts = filter(lambda t:t is not None and t != "", hosts)
    return hosts

async def async_get(url,sequence,loop):
    print(f"{sequence} start post time : {time.strftime('%X')}")
    resp = await loop.run_in_executor(None,requests.get,url)
    print(f"{sequence} response time : {time.strftime('%X')}")
    returntext = resp.text.strip()
    return {url:returntext}


async def main_gather():
    loop = asyncio.get_event_loop()
    tasks = []
    for i,url in enumerate(adblock_srcs):
        tasks.append(loop.create_task(async_get(url,i,loop)))
    x = await asyncio.gather(*tasks)
    x = reduce(merge_dict, x, {})
    returnset = set()
    for k,v in x.items():
        returnset = returnset.union(set(
            txt_to_hostlist(v, srcurl=k)
            ))
    returnset = list(map(lambda t: f"address=/{t}/127.0.0.1\naddress=/{t}/::1", returnset ))
    # f"host-record=/{t}/127.0.0.1,::1"
    print(f"complete processing adblock list, {len(returnset)} results.")
    return returnset

if __name__ == '__main__':
    starttime = time.time()
    adblocklist = asyncio.run(main_gather())
    adblocklist = "\n".join(adblocklist)
    adblockcontent = """
host-record=dialer,192.168.1.1
host-record=1frouter,192.168.1.30
host-record=2frouter,192.168.1.15
host-record=rpi4,192.168.1.200
host-record=openwrt,192.168.1.10
host-record=k8svm01,192.168.1.220
host-record=k8svm02,192.168.1.103

domain-needed
bogus-priv
expand-hosts

listen-address=192.168.1.200,127.0.0.1,0.0.0.0,fe80:0000:0000:0000:7717:80d1:1c63:2e9a,::
resolv-file=/etc/resolv.conf.dnsmasq
auth-server=end0
auth-zone=10.0.0.0/8,192.168.0.1/16
cache-size=10000
interface=end0
interface=lo
local=/lan/
domain=lan
    """
    adblockcontent = f"{adblocklist}\n{adblockcontent.strip()}"
    script_dir = Path( __file__ ).parent
    conf_path = script_dir/"adblock_local_list.conf"
    conf_path.write_text(adblockcontent)
    endtime = time.time()
    print(f"wrote to {conf_path} done. consumed: {endtime - starttime}, complete time: {time.localtime(endtime)}")

# DC:A6:32:6E:FD:FB
# 2001:b011:8004:5434:c00f:31dc:f614:de3b

# resolve any name contains lan with 172.31.0.1
# server=/lan/172.31.0.1

# sets up the local "lan" domain
# local=/lan/

# Wildcard DNS Entry.
# address=/.exception.lan/192.168.101.125/

# define some host names
# address=/open.lan/192.168.101.120


# systemctl restart dnsmasq.service
# /home/tj/Documents/Envs/kodi/bin/python /home/tj/raspberrypi_applications/home/pi/Documents/scripts/iptvmerge/adblock_gen_list.py