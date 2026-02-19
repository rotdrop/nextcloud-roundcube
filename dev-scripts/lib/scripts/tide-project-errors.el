;;; This is expected to be loaded in batch mode together like this:
;;;
;;; emacs --batch --file FILE.ts -l THIS_SCRIPT.el

(require 'package)
(add-to-list 'package-archives
             '("melpa" . "https://melpa.org/packages/") t)
(add-to-list 'package-archives
             '("melpa-stable" . "https://stable.melpa.org/packages/") t)
(package-initialize)


(let ((file buffer-file-truename))
  (find-file file)
  (tide-setup)
  (tide-mode)
  (customize-set-variable 'tide-server-max-response-length 1024000)
  ;; (tide-command:projectInfo (lambda (response)
  ;;                             (tide-on-response-success response
  ;;                                 (tide-display-errors (tide-plist-get response :body :fileNames) file))
  ;;                             )
  ;;                           t
  ;;                           file)
  (tide-project-errors)
  (let ((error-buffer-name (tide-project-errors-buffer-name))
        (error-buffer nil)
        (wait-states 0)
        (old-error-buffer-size 0)
        (error-buffer-size 0)
        (same-size-rounds 0)
        (sleep-seconds 2)
        (timeout-seconds 30)
        )
    (while (and (< (* wait-states sleep-seconds) timeout-seconds) (< same-size-rounds 4))
      (message ".")
      (sleep-for sleep-seconds)
      (if error-buffer
          (progn
            (setq error-buffer-size (buffer-size error-buffer))
            (if (and (> error-buffer-size 0) (eq old-error-buffer-size error-buffer-size))
                (setq same-size-rounds (+ same-size-rounds 1))
              )
            (setq old-error-buffer-size error-buffer-size)
            )
        (setq error-buffer (get-buffer error-buffer-name))
        )
      ;; (message "SIZE %s #SAME SIZE %s" error-buffer-size same-size-rounds)
      (setq wait-states (+ wait-states 1))
      )
    (if error-buffer
        (with-current-buffer error-buffer
          (save-restriction
            (widen)
            (princ (buffer-substring-no-properties (point-min) (point-max))))))
    )
  )
