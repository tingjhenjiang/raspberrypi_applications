# %%
import os
from pathlib import Path
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.common.exceptions import TimeoutException
# %%
checkDirectory = "/media/pi/603C27FC115D1BD8/Videos"
try:
    drive_mounted = os.path.isdir(checkDirectory)
except:
    drive_mounted = False
if drive_mounted:
    print(f"Directory {checkDirectory} exists")
    os._exit(0)
else:
    print(f"Directory {checkDirectory} does not exist")

# %%
with open(Path(os.path.dirname(__file__)) / 'routerLoginPwd.txt', mode='r') as pwdfile:
    routerLoginPwd = pwdfile.read().strip()

def createdriver(src='rpilocal'):
    options = webdriver.ChromeOptions()
    for elem_opt in ["--headless=new","user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",'lang=zh_TW.UTF-8',"--disable-dev-shm-usage",'--no-sandbox','--incognito',"start-maximized"]:
        options.add_argument(elem_opt)
    if src=='rpilocal':
        driver_path = "/usr/bin/chromedriver"
        cService = webdriver.ChromeService(executable_path=driver_path)
        driver = webdriver.Chrome(service = cService, options = options)
    else:
        host = 'http://localhost:4444/wd/hub'
        driver = webdriver.Remote(
            command_executor=host,
            options=options,
        )
    return driver
# %%
driver = createdriver('rpilocal')
# %%
driver.get("http://192.168.1.30")

for targetxpath_pairs in [
    ("//input[@type='password']",25),
    ("//a[@title='登入']",25),
    ("//div[@id='user-conflict-prompt-btn-ok']//a[@class='button-button']",3), #關閉重複登入提醒
    ("//div[@id='cloud-login-msg']//a[@class='widget-close msg-close']",25), #關閉雲端功能提醒
    ("//li[@navi-value='advanced']",10),
    ("//li[@navi-value='system']",5),
    ("//li[@navi-value='reboot']",5),
    ("//div[@id='reboot-button']",5),
    ]:
    targetxpath,waitseconds = targetxpath_pairs[0],targetxpath_pairs[1]
    print(f"now in xpath {targetxpath} for {waitseconds} seconds.")
    try:
        element = WebDriverWait(driver, waitseconds).until(
                EC.element_to_be_clickable((By.XPATH, targetxpath))
            )
        if targetxpath=="//input[@type='password']":
            element.send_keys(routerLoginPwd)
            print(f"keying password done!")
        else:
            element.click()
            print(f"xpath {targetxpath} click done!")
    except TimeoutException as e:
        print(f"time out at {targetxpath}")

# %%
driver.quit()