#!/usr/bin/env bash
set -euo pipefail
BOLD='\033[1m'; CYAN='\033[0;36m'; GREEN='\033[0;32m'
YELLOW='\033[1;33m'; RESET='\033[0m'

divider() { echo -e "${CYAN}══════════════════════════════════════════════${RESET}"; }
header()  { divider; echo -e "${BOLD}$1${RESET}"; divider; }

header "1. FILE & DIR COUNT"
echo "Total files  : $(find . -not -path './.git/*' -type f 2>/dev/null | wc -l)"
echo "Total dirs   : $(find . -not -path './.git/*' -type d 2>/dev/null | wc -l)"
echo "PHP files    : $(find app tests database -type f -name '*.php' 2>/dev/null | wc -l || echo 0)"
echo "Blade files  : $(find resources -type f -name '*.blade.php' 2>/dev/null | wc -l || echo 0)"
echo "Markdown docs: $(find docs -type f -name '*.md' 2>/dev/null | wc -l || echo 0)"
echo "Migrations   : $(find database/migrations -type f -name '*.php' 2>/dev/null | wc -l || echo 0)"
echo "Test files   : $(find tests -type f -name '*.php' 2>/dev/null | wc -l || echo 0)"
echo "Route files  : $(find routes -type f -name '*.php' 2>/dev/null | wc -l || echo 0)"

header "2. LINES OF CODE (LOC)"
echo -e "${YELLOW}PHP (app/):${RESET}"
find app -type f -name '*.php' 2>/dev/null | xargs wc -l 2>/dev/null | tail -1 || echo "0 total"
echo -e "${YELLOW}PHP (tests/):${RESET}"
find tests -type f -name '*.php' 2>/dev/null | xargs wc -l 2>/dev/null | tail -1 || echo "0 total"
echo -e "${YELLOW}PHP (database/):${RESET}"
find database -type f -name '*.php' 2>/dev/null | xargs wc -l 2>/dev/null | tail -1 || echo "0 total"
echo -e "${YELLOW}Blade (resources/):${RESET}"
find resources -type f -name '*.blade.php' 2>/dev/null | xargs wc -l 2>/dev/null | tail -1 || echo "0 total"
echo -e "${YELLOW}Markdown (docs/):${RESET}"
find docs -type f -name '*.md' 2>/dev/null | xargs wc -l 2>/dev/null | tail -1 || echo "0 total"

header "3. GIT COMMIT OVERVIEW"
FIRST_COMMIT=$(git log --oneline 2>/dev/null | tail -1 || echo "None")
LAST_COMMIT=$(git log --oneline 2>/dev/null | head -1 || echo "None")
TOTAL_COMMITS=$(git rev-list --count HEAD 2>/dev/null || echo 0)
echo "Total commits  : $TOTAL_COMMITS"
echo "First commit   : $FIRST_COMMIT"
echo "Last commit    : $LAST_COMMIT"

header "4. GIT COMMIT SUMMARY (Monthly)"
if git rev-parse --git-dir > /dev/null 2>&1; then
    git log --format='%ad' --date=format:'%Y-%m' | sort | uniq -c | sort -k2
else
    echo "Bukan repositori git."
fi

header "5. COMMIT FREQUENCY (days with commits)"
if git rev-parse --git-dir > /dev/null 2>&1; then
    git log --format='%ad' --date=format:'%Y-%m-%d' | sort -u | wc -l | xargs echo "Unique days with commits:"
    echo ""
    echo -e "${YELLOW}Commits per weekday:${RESET}"
    git log --format='%ad' --date=format:'%A' | sort | uniq -c | sort -rn
else
    echo "Bukan repositori git."
fi

header "6. TOP 20 MOST CHANGED FILES (churn)"
if git rev-parse --git-dir > /dev/null 2>&1; then
    git log --name-only --format='' | grep '\.php$' | sort | uniq -c | sort -rn | head -20 || true
else
    echo "Bukan repositori git."
fi

header "7. TOP 15 BIGGEST PHP FILES (LOC)"
find app tests -type f -name '*.php' 2>/dev/null | xargs wc -l 2>/dev/null | sort -rn | head -16 || true

header "8. MIGRATION TIMELINE"
if [ -d "database/migrations" ] && [ "$(ls -A database/migrations/*.php 2>/dev/null)" ]; then
    ls database/migrations/*.php 2>/dev/null | sed 's|database/migrations/||' | \
        awk -F'_' '{print $1"-"$2"-"$3}' | sort | uniq -c
else
    echo "Tidak ada file migrasi ditemukan."
fi

header "9. TEST COUNT PER DOMAIN"
if [ -d "tests/Feature" ]; then
    for dir in tests/Feature/*/ ; do
        [ -d "$dir" ] || continue
        domain=$(basename "$dir")
        count=$(find "$dir" -type f -name '*.php' 2>/dev/null | wc -l)
        echo "  $domain: $count"
    done | sort -t: -k2 -rn || true
else
    echo "Folder tests/Feature tidak ditemukan."
fi

header "10. PORT / ADAPTER / CORE RATIO"
ports=$(find app/Ports -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
adapters_in=$(find app/Adapters/In -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
adapters_out=$(find app/Adapters/Out -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
core=$(find app/Core -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
application=$(find app/Application -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
t_files=$(find tests -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
a_files=$(find app -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
echo "  Ports        : $ports"
echo "  Adapters/In  : $adapters_in"
echo "  Adapters/Out : $adapters_out"
echo "  Core         : $core"
echo "  Application  : $application"
echo "  Ratio test:src = $t_files:$a_files"

header "11. STRICT_TYPES COVERAGE"
total_php=$(find app -type f -name '*.php' 2>/dev/null | wc -l || echo 0)
if [ "$total_php" -gt 0 ]; then
    strict=$(grep -rl "declare(strict_types=1)" app --include="*.php" 2>/dev/null | wc -l || echo 0)
    pct=$(echo "scale=1; $strict * 100 / $total_php" | bc)
    echo "  Files with strict_types : $strict / $total_php (${pct}%)"
else
    echo "  Tidak ada file PHP di folder app/."
fi

header "12. FINAL CLASS USAGE"
count_app_php_matching() {
    local pattern="$1"

    (find app -type f -name '*.php' -exec grep -lE "$pattern" {} + 2>/dev/null || true) | wc -l | tr -d '[:space:]'
}

total=$(count_app_php_matching '^(final[[:space:]]+|abstract[[:space:]]+)?class[[:space:]]')
final=$(count_app_php_matching '^final[[:space:]]+class[[:space:]]')
abstract=$(count_app_php_matching '^abstract[[:space:]]+class[[:space:]]')
iface=$(count_app_php_matching '^interface[[:space:]]')
echo "  final class    : $final"
echo "  abstract class : $abstract"
echo "  interface      : $iface"
echo "  (approx open class: $((total - final - abstract - iface)))"

header "13. READONLY / IMMUTABILITY SIGNALS"
readonly_count=$(grep -r "private readonly\|public readonly\|protected readonly" app --include="*.php" 2>/dev/null | wc -l || echo 0)
immutable_dt=$(grep -r "DateTimeImmutable" app --include="*.php" 2>/dev/null | wc -l || echo 0)
echo "  'readonly' properties  : $readonly_count occurrences"
echo "  DateTimeImmutable uses : $immutable_dt occurrences"

divider
echo -e "${GREEN}${BOLD}  REPORT SELESAI.${RESET}"
divider
