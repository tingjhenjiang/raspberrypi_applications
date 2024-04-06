from pathlib import Path
import time

parent = Path(__file__).resolve().parent
monitor_file = parent/"controlrpi.txt"

while True:
    readcontent = monitor_file.read_text(encoding="utf-8")
    writecontent = monitor_file.write_text("",encoding="utf-8")
    match readcontent:
        case 'reboot':
            import subprocess
            print(readcontent)
            shell_res = subprocess.run(["sudo", "reboot"], capture_output=True, text=True)
            print(shell_res)
        case 'terminatekodi':
            print(readcontent)
            # send_control_req('Application.Quit')
        case _:
            pass
    time.sleep(20)