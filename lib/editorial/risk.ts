// A conservative starting point, not a replacement for the editor's assessment.
export function suggestedEditorialRisk(text:string):'standard'|'sensitive'{
 return /\b(child(?:ren)?|pupils?|school|young people|crime|criminal|arrest(?:ed)?|police|court|alleg(?:ation|ed)|victim|abuse|assault|violence|murder|suicide|death|died|dead|injur\w*|hospital|health|patient|medical|homeless\w*|evacuat\w*|vulnerab\w*|safeguard\w*)\b/i.test(text)?'sensitive':'standard';
}
