package productcatalog

import productcatalogusecase "pos-go/internal/modules/productcatalog/usecase"

type ProductVersionResponse struct {
	ProductID        string `json:"product_id"`
	RevisionNo       int    `json:"revision_no"`
	EventName        string `json:"event_name"`
	ChangedByActorID string `json:"changed_by_actor_id"`
	ChangeReason     string `json:"change_reason"`
	ChangedAt        string `json:"changed_at"`
}

func FromProductVersions(result productcatalogusecase.ListProductVersionsResult) []ProductVersionResponse {
	responses := make([]ProductVersionResponse, 0, len(result.Items))
	for _, item := range result.Items {
		responses = append(responses, ProductVersionResponse{
			ProductID:        item.ProductID,
			RevisionNo:       item.RevisionNo,
			EventName:        item.EventName,
			ChangedByActorID: item.ChangedByActorID,
			ChangeReason:     item.ChangeReason,
			ChangedAt:        formatRFC3339(item.ChangedAt),
		})
	}

	return responses
}
