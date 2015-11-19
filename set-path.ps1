$PATH = [Environment]::GetEnvironmentVariable("PATH")
$composerBin = "__COMPOSER_BIN__"
[Environment]::SetEnvironmentVariable("PATH", "$PATH;$composerBin", "Machine")