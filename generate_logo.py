import sys
import os
from PIL import Image, ImageDraw, ImageFont

def create_logo():
    # Settings
    # High resolution for better quality, can be scaled down by browser
    width = 900
    height = 150
    bg_color = (0, 0, 0, 0)
    text_color = (139, 0, 0, 255) # Dark Red #8B0000
    
    # Path to font
    # Verified existence in previous step: /Library/Fonts/Arial Unicode.ttf
    font_path = "/Library/Fonts/Arial Unicode.ttf"
    font_size = 80
    
    try:
        font = ImageFont.truetype(font_path, font_size)
    except Exception as e:
        print(f"Error loading font {font_path}: {e}")
        # Fallback to default, though it will look bad and likely miss the icon
        font = ImageFont.load_default()

    img = Image.new("RGBA", (width, height), bg_color)
    draw = ImageDraw.Draw(img)
    
    text = "✈ Allegiant Air Tickets"
    
    # Calculate text position to center it
    try:
        # Pillow >= 9.2.0
        left, top, right, bottom = draw.textbbox((0, 0), text, font=font)
        text_w = right - left
        text_h = bottom - top
    except AttributeError:
        # Older Pillow
        text_w, text_h = draw.textsize(text, font=font)
        
    x = (width - text_w) / 2
    # Vertically center. textbbox 'top' is relative to 0. 
    # Usually we just want to center the bounding box in the canvas.
    y = (height - text_h) / 2
    
    # Refine y. textbbox usually gives tight bounds. 
    # If we draw at (x, y), the top-left of the text *bounding box* should be at (x, y)?
    # No, draw.text(xy) specifies the top-left corner of the text.
    # But font rendering is complex (ascent/descent).
    # Let's just place it at calculated x, y relative to the top of the bounding box.
    # Actually, draw.text((x, y), ...) draws at that coordinate.
    # To center:
    # We want the center of the text (x + w/2, y + h/2) to be at (width/2, height/2)
    # So draw_x = width/2 - w/2
    #    draw_y = height/2 - h/2  <-- this is approximate for top-left
    # But strictly, draw.text takes the position of the starting point.
    # textbbox returns (left, top, right, bottom) relative to the anchor (default top-left (0,0)).
    # So if we draw at (0,0), the text occupies (left, top, right, bottom).
    # We want to shift it so (left+right)/2 aligns with width/2, etc.
    # shift_x = width/2 - (left+right)/2
    # shift_y = height/2 - (top+bottom)/2
    
    shift_x = width/2 - (left + right)/2
    shift_y = height/2 - (top + bottom)/2
    
    draw.text((shift_x, shift_y), text, font=font, fill=text_color)
    
    output_path = "assets/images/logo.webp"
    
    # Ensure directory exists
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    
    img.save(output_path, "WEBP")
    print(f"Generated {output_path}")

if __name__ == "__main__":
    create_logo()
