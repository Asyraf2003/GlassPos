package http

import (
	stdhttp "net/http"

	productcatalogusecase "pos-go/internal/modules/productcatalog/usecase"
	productcatalogid "pos-go/internal/presentation/http/id/productcatalog"

	"github.com/labstack/echo/v4"
)

func (h ProductCatalogHandler) List(c echo.Context) error {
	status, err := parseProductListStatus(c.QueryParam("status"))
	if err != nil {
		return err
	}

	page, err := parseOptionalIntQuery(c, "page")
	if err != nil {
		return err
	}

	perPage, err := parseOptionalIntQuery(c, "per_page")
	if err != nil {
		return err
	}

	result, err := h.list.Execute(c.Request().Context(), productcatalogusecase.ListProductsQuery{
		Search:  c.QueryParam("q"),
		Status:  status,
		Page:    page,
		PerPage: perPage,
	})
	if err != nil {
		return mapProductCatalogError(err)
	}

	return c.JSON(stdhttp.StatusOK, successEnvelope(productcatalogid.FromProductList(result)))
}

func (h ProductCatalogHandler) Lookup(c echo.Context) error {
	limit, err := parseOptionalIntQuery(c, "limit")
	if err != nil {
		return err
	}

	includeDeleted, err := parseIncludeDeleted(c)
	if err != nil {
		return err
	}

	result, err := h.lookup.Execute(c.Request().Context(), productcatalogusecase.LookupProductsQuery{
		Query:          c.QueryParam("q"),
		Limit:          limit,
		IncludeDeleted: includeDeleted,
	})
	if err != nil {
		return mapProductCatalogError(err)
	}

	return c.JSON(stdhttp.StatusOK, successEnvelope(productcatalogid.FromProductLookup(result)))
}

func (h ProductCatalogHandler) Show(c echo.Context) error {
	result, err := h.show.Execute(c.Request().Context(), productcatalogusecase.GetProductDetailQuery{
		ID: c.Param("id"),
	})
	if err != nil {
		return mapProductCatalogError(err)
	}

	return c.JSON(stdhttp.StatusOK, successEnvelope(productcatalogid.FromProductDetail(result)))
}

func (h ProductCatalogHandler) Versions(c echo.Context) error {
	result, err := h.versions.Execute(c.Request().Context(), productcatalogusecase.ListProductVersionsQuery{
		ProductID: c.Param("id"),
	})
	if err != nil {
		return mapProductCatalogError(err)
	}

	return c.JSON(stdhttp.StatusOK, successEnvelope(productcatalogid.FromProductVersions(result)))
}
