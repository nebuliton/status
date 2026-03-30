param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $RemainingArgs
)

& php artisan app:update @RemainingArgs
exit $LASTEXITCODE
