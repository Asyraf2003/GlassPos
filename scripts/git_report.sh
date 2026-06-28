#!/usr/bin/env bash
set -euo pipefail
BOLD='\033[1m'; CYAN='\033[0;36m'; GREEN='\033[0;32m'
YELLOW='\033[1;33m'; RESET='\033[0m'

divider() { echo -e "${CYAN}══════════════════════════════════════════════${RESET}"; }
header()  { divider; echo -e "${BOLD}$1${RESET}"; divider; }

header "1. FILE & DIR COUNT"
echo "Total files  : $(find . -not -path './.git/*' -type f | wc -l)"
echo "Total dirs   : $(find . -not -path './.git/*' -type d | wc -l)"
echo "PHP files    : $(find app tests database -name '*.php' | wc -l)"
echo "Blade files  : $(find resources -name '*.blade.php' | wc -l)"
echo "Markdown docs: $(find docs -name '*.md' | wc -l)"
echo "Migrations   : $(find database/migrations -name '*.php' | wc -l)"
echo "Test files   : $(find tests -name '*.php' | wc -l)"
echo "Route files  : $(find routes -name '*.php' | wc -l)"

header "2. LINES OF CODE (LOC)"
echo -e "${YELLOW}PHP (app/):${RESET}"
find app -name '*.php' | xargs wc -l 2>/dev/null | tail -1
echo -e "${YELLOW}PHP (tests/):${RESET}"
find tests -name '*.php' | xargs wc -l 2>/dev/null | tail -1
echo -e "${YELLOW}PHP (database/):${RESET}"
find database -name '*.php' | xargs wc -l 2>/dev/null | tail -1
echo -e "${YELLOW}Blade (resources/):${RESET}"
find resources -name '*.blade.php' | xargs wc -l 2>/dev/null | tail -1
echo -e "${YELLOW}Markdown (docs/):${RESET}"
find docs -name '*.md' | xargs wc -l 2>/dev/null | tail -1

header "3. GIT COMMIT OVERVIEW"
FIRST_COMMIT=$(git log --oneline | tail -1)
LAST_COMMIT=$(git log --oneline | head -1)
TOTAL_COMMITS=$(git rev-list --count HEAD)
echo "Total commits  : $TOTAL_COMMITS"
echo "First commit   : $FIRST_COMMIT"
echo "Last commit    : $LAST_COMMIT"
echo ""
echo -e "${YELLOW}Commits per month:${RESET}"
git log --format='%ad' --date=format:'%Y-%m' | sort | uniq -c | sort -k2

header "4. AUTHOR CONTRIBUTION"
echo -e "${YELLOW}Commits per author:${RESET}"
git shortlog -sn --all
echo ""
echo -e "${YELLOW}Lines added/removed per author:${RESET}"
git log --pretty="%an" --numstat | awk '
  /^[0-9]/ { added[$author] += $1; removed[$author] += $2 }
  /^[^0-9\t]/ { author = $0 }
  END { for (a in added) printf "%s: +%d / -%d\n", a, added[a], removed[a] }
'

header "5. COMMIT FREQUENCY (days with commits)"
git log --format='%ad' --date=format:'%Y-%m-%d' | sort -u | wc -l | xargs echo "Unique days with commits:"
echo ""
echo -e "${YELLOW}Commits per weekday:${RESET}"
git log --format='%ad' --date=format:'%A' | sort | uniq -c | sort -rn

header "6. TOP 20 MOST CHANGED FILES (churn)"
git log --name-only --format='' | grep '\.php$' | sort | uniq -c | sort -rn | head -20

header "7. TOP 15 BIGGEST PHP FILES (LOC)"
find app tests -name '*.php' | xargs wc -l 2>/dev/null | sort -rn | head -16

header "8. MIGRATION TIMELINE"
ls database/migrations/*.php 2>/dev/null | sed 's|database/migrations/||' | \
  awk -F'_' '{print $1"-"$2"-"$3}' | sort | uniq -c

header "9. TEST COUNT PER DOMAIN"
for dir in tests/Feature/*/; do
  domain=$(basename "$dir")
  count=$(find "$dir" -name '*.php' | wc -l)
  echo "  $domain: $count"
done | sort -t: -k2 -rn

header "10. PORT / ADAPTER / CORE RATIO"
ports=$(find app/Ports -name '*.php' | wc -l)
adapters_in=$(find app/Adapters/In -name '*.php' | wc -l)
adapters_out=$(find app/Adapters/Out -name '*.php' | wc -l)
core=$(find app/Core -name '*.php' | wc -l)
application=$(find app/Application -name '*.php' | wc -l)
echo "  Ports        : $ports"
echo "  Adapters/In  : $adapters_in"
echo "  Adapters/Out : $adapters_out"
echo "  Core         : $core"
echo "  Application  : $application"
echo "  Ratio test:src = $(find tests -name '*.php' | wc -l):$(find app -name '*.php' | wc -l)"

header "11. STRICT_TYPES COVERAGE"
total_php=$(find app -name '*.php' | wc -l)
strict=$(grep -rl "declare(strict_types=1)" app | wc -l)
pct=$(echo "scale=1; $strict * 100 / $total_php" | bc)
echo "  Files with strict_types : $strict / $total_php (${pct}%)"

header "12. FINAL CLASS USAGE"
total=$(find app -name '*.php' | xargs grep -l "^class\|^final class\|^abstract class" 2>/dev/null | wc -l)
final=$(find app -name '*.php' | xargs grep -l "^final class" 2>/dev/null | wc -l)
abstract=$(find app -name '*.php' | xargs grep -l "^abstract class" 2>/dev/null | wc -l)
iface=$(find app -name '*.php' | xargs grep -l "^interface" 2>/dev/null | wc -l)
echo "  final class    : $final"
echo "  abstract class : $abstract"
echo "  interface      : $iface"
echo "  (approx open class: $((total - final - abstract - iface)))"

header "13. READONLY / IMMUTABILITY SIGNALS"
readonly_count=$(grep -r "private readonly\|public readonly\|protected readonly" app --include="*.php" | wc -l)
immutable_dt=$(grep -r "DateTimeImmutable" app --include="*.php" | wc -l)
echo "  'readonly' properties  : $readonly_count occurrences"
echo "  DateTimeImmutable uses : $immutable_dt occurrences"

divider
echo -e "${GREEN}${BOLD}  REPORT SELESAI.${RESET}"
divider
