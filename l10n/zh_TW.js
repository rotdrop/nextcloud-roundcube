OC.L10N.register(
    "mail_roundcube",
    {
    "Unknown admin setting: \"%1$s\"" : "未知的管理設定：「%1$s」",
    "The admin setting \"%1$s\" is read-only" : "管理設定「%1$s」是唯讀的",
    "Scheme of external URL must be one of \"http\" or \"https\", but nothing was specified." : "外部 URL 格式必須為「http」或「https」其中之一，但並未指定任何內容。",
    "Scheme of external URL must be one of \"http\" or \"https\", \"%s\" given." : "外部 URL 格式必須為「http」或「https」其中之一，指定了「%s」。",
    "Host-part of external URL seems to be empty" : "外部 URL 的主機部份似乎是空的",
    "Value \"%1$s\" for setting \"%2$s\" is not convertible to boolean." : "設定「%2$s」的值「%1$s」無法轉換為布林值。",
    "true" : "true",
    "false" : "false",
    "Unknown personal setting: \"%1$s\"" : "未知的個人設定：「%1$s」",
    "The personal setting \"%1$s\" is read-only" : "個人設定「%1$s」是唯讀的",
    "Unknown personal setting: \"%s\"." : "未知的個人設定：「%s」。",
    "RoundCube Integration" : "RoundCube 整合",
    "Installation problem; the required resource \"%1$s\" of type \"%2$s\" is not installed on the server, please contact the system administrator!" : "安裝問題；伺服器上沒有安裝必要的類型為「%2$s」的資源「%1$s」，請聯絡系統管理員！",
    "User" : "使用者",
    "Password" : "密碼",
    "Login succeeded." : "登入成功。",
    "Login failed." : "登入失敗。",
    "Error, caught an exception." : "錯誤，捕捉到例外。",
    "Caused by previous exception" : "由先前的例外造成",
    "bytes" : "位元組",
    "The supplied color-string \"%s\" seems to be invalid." : "提供的顏色字串「%s」似乎是無效的。",
    "The input color values are invalid." : "輸入的顏色值無效。",
    "RoundCube Mail" : "RoundCube 郵件",
    "RoundCube Web Mail" : "RoundCube 網路郵件",
    "Embed an installation of RoundCube into Nextcloud" : "將 Roundcube 嵌入至 Nextcloud 中",
    "Integrates an existing RoundCube standalone email-webapp into NextCloud, with optional SSO." : "將現有的 RoundCube 獨立電子郵件網路應用程式整合至 Nextcloud 中，包含選擇性的 SSO。",
    "pick a color" : "挑選顏色",
    "open" : "開啟",
    "submit" : "submit",
    "revert color" : "還原顏色",
    "restore palette" : "還原調色盤",
    "factory reset palette" : "將調色盤重設為出廠預設值",
    "Custom Color" : "自訂顏色",
    "Provided data is not a valid SVG image: \"{data}\"." : "提供的資料不是有效的 SVG 影像：「{data}」。",
    "Choose a folder" : "選擇資料夾",
    "Choose a prefix-folder" : "選擇前綴資料夾",
    "Invalid path selected: \"{dir}\"." : "選取了無效路徑：「{dir}」。",
    "Selected path: \"{dir}/{base}/\"." : "已選取的路徑：「{dir}/{base}/」。",
    "Please select an item!" : "請選取項目！",
    "An empty value is not allowed, please make your choice!" : "不允許空值，請自行選擇！",
    "Click to submit your changes." : "點擊以遞交您的變更。",
    "Reset Changes" : "重設變更",
    "Clear Selection" : "清除選取範圍",
    "Config template has been copied to the clipboard." : "設定範本已複製到剪貼簿",
    "Failed copying the config template to the clipboard: {reason}." : "無法將設定範本複製到剪貼簿：{reason}。",
    "Embedded RoundCube, Admin Settings" : "嵌入式 Roundcube，管理設定",
    "Roundcube Installation" : "Roundcube 安裝",
    "RoundCube Installation Path" : "RoundCube 安裝路徑",
    "RoundCube path can be entered relative to the Nextcloud server" : "可以輸入相對於 Nextcloud 伺服器的 Roundcube 路徑",
    "Email Address Selection" : "電子郵件地址選擇",
    "Cloud Login-Id" : "雲端登入 ID",
    "User ID" : "使用者 ID",
    "Email Domain" : "電子郵件網域",
    "User's Preferences" : "使用者的偏好設定",
    "User's Choice" : "使用者的選擇",
    "Fixed Single Address" : "固定單一位置",
    "Global Email Login" : "全域電子郵件登入",
    "Global email user-name for Roundcube for all users" : "所有使用者的 Roundcube 全域電子郵件使用者名稱",
    "Email Address" : "電子郵件地址",
    "Global Email Password" : "全域電子郵件密碼",
    "Email Password" : "電子郵件密碼",
    "Global email password for Roundcube for all users" : "所有使用者的 Roundcube 全域電子郵件密碼",
    "Advanced Settings" : "進階設定",
    "Force single sign on (disables custom password)." : "強制單一登入（停用自訂密碼）。",
    "Show RoundCube top information bar (shows logout button)." : "顯示 RoundCube 頂部資訊列（顯示登出按鈕）。",
    "Disable when debugging with self-signed certificates." : "使用自行簽署憑證除錯時停用。",
    "Enable SSL verification." : "啟用 SSL 驗證。",
    "Encrypt per-user data -- in particular their email passwords -- with their personal cloud password. This implies that these settings will be lost when users forget their passwords. If unchecked the email login credentials are still protected by the server secret. The latter implies that an administrator is able to decrypt the login credentials, but the configuration data survives user password-loss." : "使用個人雲端密碼加密每個使用者的資料，尤其是他們的電子郵件密碼。這代表了當使用者忘記密碼時，這些設定將會遺失。若未勾選，電子郵件登入憑證仍受伺服器密碼保護。這代表了管理員可以解密登入憑證，但設定資料在使用者密碼遺失後仍然存在。",
    "Per-user encryption of config values." : "設定值的每個使用者加密。",
    "RoundCube CardDAV Tag" : "RoundCube CardDAV 標籤",
    "Tag of a preconfigured CardDAV account pointing to the cloud addressbook. See the documentation of the RCMCardDAV plugin." : "指向雲端通訊錄的預先設定 CardDAV 帳號的標籤。請參閱 RCMardDAV 外掛程式的文件。",
    "RCMCardDAV Plugin Configuration" : "RCMCardDAV 外掛程式設定",
    "ClipBoard" : "剪貼簿",
    "Below is a configuration snippet which may or may not work with the current version of the RoundCube CardDAV plugin. The configuration shown below is just a suggestion and will not automatically be registered with the RoundCube app. It is your responsibility to configure the RoundCube CardDAV plugin correctly. Please have a look at the explanations in the README.md file." : "以下是一個設定片段，可能適用也可能不適用於目前版本的 RoundCube CardDAV 外掛程式。下面顯示的設定只是建議，不會自動註冊到 RoundCube 應用程式。您有責任正確設定 RoundCube CardDAV 外掛程式。請見 README.md 檔案中的解釋。",
    "Unable to configure the CardDAV integration for \"{emailUserId}\"." : "無法為「{emailUserId}」設定 CardDAV 整合。",
    "Unable to obtain email credentials for \"{emailUserId}\". Please check your personal Roundcube settings." : "無法擷取「{emailUserId}」的電子郵件身份驗證。請檢查您的個人 Roundcube 設定。",
    "RoundCube Wrapper for Nextcloud" : "Nextcloud 的 RoundCube Wrapper",
    "Globally configured as USERID@{emailDefaultDomainAdmin}" : "全域設定為 USERID@{emailDefaultDomainAdmin}",
    "Globally configured as user's email address, see user's personal settings." : "全域設定為使用者的電子郵件地址，檢視使用者個人設定。",
    "Globally configured as {fixedSingleEmailAddressAdmin}" : "全域設定為 {fixedSingleEmailAddressAdmin}",
    "Please specify an email address to use with RoundCube." : "請指定用於 Roundcube 的電子郵件地址。",
    "Globally configured by the administrator" : "已被管理員全域設定",
    "Single sign-on is globally forced \"on\"." : "單一登入全域強制為「開啟」。",
    "Email password for RoundCube, if needed." : "Roundcube 的電子郵件密碼（若需要）。",
    "Embedded RoundCube, Personal Settings" : "嵌入式 Roundcube，個人設定",
    "Email Login Name" : "電子郵件登入名稱"
},
"nplurals=1; plural=0;");
