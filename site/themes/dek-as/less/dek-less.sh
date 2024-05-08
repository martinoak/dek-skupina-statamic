#!/bin/bash

lessc dek.less --source-map ../css/uikit-dek.css
lessc dek.less --clean-css="--s1" ../css/uikit-dek.min.css
