(function($) {
	describe("LeftAndMain.Menu", function() {
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;		
		$.entwine.synchronous_mode();
		
		describe('Element retrieval by link', function() {
			
			var m;

			beforeEach(function() {
				loadFixture('LeftAndMain.Menu.fixture.html');
				m = $('.cms-menu-list');
			});

			it('doesnt match for empty url', function() {
				expect(m.findByUrl('')).toBeFalsy();
			});
			
			it('exact mactch on first level', function() {
				expect(m.findByUrl('item1')).toEqual(m.find('.item1'));
			});
			
			it('exact mactch on second level', function() {
				expect(m.findByUrl('item1/item1-1')).toEqual(m.find('.item1-1'));
			});
			
			it('exact mactch on first level with trailing slash', function() {
				expect(m.findByUrl('/item3')).toEqual(m.find('.item3'));
			});
			
			it('fuzzy mactch on first level', function() {
				expect(m.findByUrl('item1/other')).toEqual(m.find('.item1'));
			});
			
			it('fuzzy mactch on second level', function() {
				expect(m.findByUrl('item2/item2-2/other')).toEqual(m.find('.item2-2'));
			});
			
			it('fuzzy mactch with GET parameters', function() {
				expect(m.findByUrl('item3/?foo=bar')).toEqual(m.find('.item3'));
			});
			
		});

	});
}(jQuery));