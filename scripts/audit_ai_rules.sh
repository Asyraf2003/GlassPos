#!/usr/bin/env bash
set -eu

echo '== AI_RULES structure =='
find AI_RULES -maxdepth 3 -type f | sort

echo
echo '== required files check =='
required_files='
docs/01_standards/00_INDEX.md
docs/01_standards/01_DECISION_POLICY.md
docs/01_standards/02_GPT_BOOTSTRAP_PROMPT.md
docs/01_standards/03_SESSION_START_PROTOCOL.md
docs/01_standards/04_HANDOFF_TEMPLATE.md
docs/01_standards/05_FINAL_REVIEW_CHECKLIST.md
docs/01_standards/10_CORE/10_SCOPE_AND_FACTS.md
docs/01_standards/10_CORE/11_BLUEPRINT_FIRST.md
docs/01_standards/10_CORE/12_STEP_BY_STEP_EXECUTION.md
docs/01_standards/10_CORE/13_PROOF_AND_PROGRESS.md
docs/01_standards/20_WORKFLOW/20_RESPONSE_STRUCTURE.md
docs/01_standards/20_WORKFLOW/21_ACTIVE_STEP_POLICY.md
docs/01_standards/20_WORKFLOW/22_OPTION_EVALUATION.md
docs/01_standards/20_WORKFLOW/23_HANDOFF_POLICY.md
docs/01_standards/30_OUTPUT/30_FILE_DELIVERY.md
docs/01_standards/30_OUTPUT/31_MARKDOWN_OUTPUT_RULE.md
docs/01_standards/30_OUTPUT/32_BLADE_RULE.md
docs/01_standards/30_OUTPUT/33_TERMINAL_COMMAND_DELIVERY.md
docs/01_standards/40_ARCHITECTURE/40_HEXAGONAL_BASELINE.md
docs/01_standards/40_ARCHITECTURE/41_PUBLIC_CONTRACTS.md
docs/01_standards/40_ARCHITECTURE/42_ERROR_HANDLING_AND_REDACTION.md
docs/01_standards/40_ARCHITECTURE/43_DEBUG_GATING.md
docs/01_standards/40_ARCHITECTURE/44_AUDIT_AND_DOD.md
docs/01_standards/50_DOMAIN_KASIR/50_FINAL_DOMAIN_MAP.md
docs/01_standards/50_DOMAIN_KASIR/51_UI_TERMS_AND_STATUS.md
docs/01_standards/50_DOMAIN_KASIR/52_PAYMENT_LIFECYCLE.md
docs/01_standards/50_DOMAIN_KASIR/53_REPORTING_BOUNDARY.md
docs/01_standards/60_STACK/60_LARAVEL_RULES.md
docs/01_standards/60_STACK/61_GO_RULES.md
docs/01_standards/60_STACK/62_AWS_BASELINE.md
docs/01_standards/99_CHANGELOG.md
'
printf '%s\n' "$required_files" | sed '/^$/d' | while IFS= read -r f; do
  if [ -f "$f" ]; then
    echo "[OK] $f"
  else
    echo "[MISSING] $f"
    exit 1
  fi
done

echo
echo '== keyword checks =='
grep -n 'Mandatory Read Order' docs/01_standards/00_INDEX.md
grep -n 'Rule Hierarchy' docs/01_standards/01_DECISION_POLICY.md
grep -n 'Implementation Gate' docs/01_standards/10_CORE/11_BLUEPRINT_FIRST.md
grep -n 'Hanya satu step aktif' docs/01_standards/02_GPT_BOOTSTRAP_PROMPT.md
grep -n 'Target akhir lifecycle pembayaran adalah partial payment eksplisit' docs/01_standards/50_DOMAIN_KASIR/52_PAYMENT_LIFECYCLE.md
grep -n 'outer fence wajib menggunakan `text`' docs/01_standards/30_OUTPUT/31_MARKDOWN_OUTPUT_RULE.md
grep -n '## Next step' docs/01_standards/04_HANDOFF_TEMPLATE.md

echo
echo '== audit complete =='
