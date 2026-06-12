package http

type responseEnvelope struct {
	Success bool `json:"success"`
	Data    any  `json:"data"`
	Meta    any  `json:"meta"`
}

func successEnvelope(data any) responseEnvelope {
	return responseEnvelope{
		Success: true,
		Data:    data,
		Meta:    map[string]any{},
	}
}
