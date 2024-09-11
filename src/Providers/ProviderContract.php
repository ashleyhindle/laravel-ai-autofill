<?php

namespace AshleyHindle\AiAutofill\Providers;

use AshleyHindle\AiAutofill\AutofillContext;
use AshleyHindle\AiAutofill\AutofillResults;

interface ProviderContract
{
    public function autofill(AutofillContext $context): AutofillResults|array;
}
