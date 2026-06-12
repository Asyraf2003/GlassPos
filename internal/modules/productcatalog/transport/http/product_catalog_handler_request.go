package http

import (
	stdhttp "net/http"
	"strconv"
	"strings"

	httpmw "pos-go/internal/transport/http/middleware"

	"github.com/labstack/echo/v4"
)

type productUpsertRequest struct {
	Code                 string `json:"kode_barang"`
	Name                 string `json:"nama_barang"`
	Brand                string `json:"merek"`
	Size                 *int   `json:"ukuran"`
	SalePriceRupiah      int64  `json:"harga_jual"`
	ReorderPointQty      *int   `json:"reorder_point_qty"`
	CriticalThresholdQty *int   `json:"critical_threshold_qty"`
	Reason               string `json:"reason"`
}

type productLifecycleRequest struct {
	Reason string `json:"reason"`
}

func parseOptionalIntQuery(c echo.Context, name string) (int, error) {
	raw := strings.TrimSpace(c.QueryParam(name))
	if raw == "" {
		return 0, nil
	}

	value, err := strconv.Atoi(raw)
	if err != nil {
		return 0, echo.NewHTTPError(stdhttp.StatusBadRequest, name+" must be an integer")
	}

	return value, nil
}

func parseProductListStatus(raw string) (string, error) {
	switch strings.ToLower(strings.TrimSpace(raw)) {
	case "", "active", "deleted", "all":
		return strings.ToLower(strings.TrimSpace(raw)), nil
	default:
		return "", echo.NewHTTPError(stdhttp.StatusBadRequest, "status must be active, deleted, or all")
	}
}

func parseIncludeDeleted(c echo.Context) (bool, error) {
	raw := strings.TrimSpace(c.QueryParam("include_deleted"))
	if raw == "" {
		return false, nil
	}

	includeDeleted, err := strconv.ParseBool(raw)
	if err != nil {
		return false, echo.NewHTTPError(stdhttp.StatusBadRequest, "include_deleted must be a boolean")
	}

	return includeDeleted, nil
}

func actorIDFromRequest(c echo.Context) string {
	principal, ok := httpmw.PrincipalFromContext(c.Request().Context())
	if !ok {
		return ""
	}

	return principal.AccountID
}
