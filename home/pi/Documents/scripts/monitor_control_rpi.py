from pathlib import Path
import time

parent = Path(__file__).resolve().parent

# monitor_file_wakeup2fpc = parent/"controlrpi_wakeup2fpc.txt"
while True:
    monitor_files = list(parent.glob("controlrpi*.txt"))
    check_files = {k:f"controlrpi_{k}.txt" for k in ['reboot','wakeup2f','wakeuptjlaptop']}
    monitor_files_nameonly = {n.name:i for i,n in enumerate(monitor_files)}
    if_fileexist = {k:(v in monitor_files_nameonly) for k,v in check_files.items()}
    for k,v in check_files.items():
        match if_fileexist[k]:
            case True | 'reboot':
                import subprocess
                corres_filepath = monitor_files[monitor_files_nameonly[check_files[k]]]
                renameres = corres_filepath.rename(parent/"controlrpi.txt")
                print(f"controlrpi_{k} file exists! rename results: {renameres}")
                time.sleep(1)
                if k=='reboot':
                    shell_commands = ["sudo", "reboot"] 
                elif k=='wakeup2f':
                    shell_commands = ['sudo','etherwake','-b','-i','end0','-D','10:7B:44:7A:31:B2']
                elif k=='wakeuptjlaptop':
                    shell_commands = ['sudo','etherwake','-b','-i','end0','-D','3C:91:80:93:98:25']
                else:
                    shell_commands = []
                if len(shell_commands)>0:
                    shell_res = subprocess.run(shell_commands, capture_output=True, text=True)
                    print(shell_res)
            case False | 'terminatekodi':
                # print(readcontent)
                # send_control_req('Application.Quit')
                pass
            case _:
                pass
    time.sleep(10)